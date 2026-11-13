// Fingerprint matcher ("fingerprint helper") for the school system. PHP cannot compare fingerprints,
// so pictures from a USB reader come here. It turns a picture into a template, and finds whose
// template a new picture matches. It keeps nothing: templates are stored by the school system.
//
// It can be used two ways, both at once:
//   By the server (VPS or XAMPP on this computer): PHP calls it with the X-Matcher-Key header.
//   By the browser on a school computer (needed with shared hosting): the school website's pages
//   call http://127.0.0.1:5100 directly. Only the websites listed in AllowedOrigins may do that.
//
// Settings come from fingerprint-helper.json next to this program, or environment variables:
//   MatcherKey / MATCHER_KEY            secret shared with the server's FINGERPRINT_MATCHER_KEY
//   AllowedOrigins / ALLOWED_ORIGINS    school website addresses, e.g. https://myschool.com
//   Urls / ASPNETCORE_URLS              where to listen. Default http://127.0.0.1:5100 (this computer only)

using SourceAFIS;

var builder = WebApplication.CreateBuilder(new WebApplicationOptions { Args = args, ContentRootPath = AppContext.BaseDirectory });
builder.Configuration.AddJsonFile(Path.Combine(AppContext.BaseDirectory, "fingerprint-helper.json"), optional: true);

var key = builder.Configuration["MatcherKey"] ?? Environment.GetEnvironmentVariable("MATCHER_KEY") ?? "";
var origins = (builder.Configuration.GetSection("AllowedOrigins").Get<string[]>()
        ?? (Environment.GetEnvironmentVariable("ALLOWED_ORIGINS") ?? "").Split(',', StringSplitOptions.RemoveEmptyEntries | StringSplitOptions.TrimEntries))
    .Select(o => o.TrimEnd('/').ToLowerInvariant()).Where(o => o != "").ToHashSet();

if (key.Length < 16 && origins.Count == 0)
{
    Console.Error.WriteLine("Nothing may use this helper yet. Set MatcherKey (16+ characters, same as FINGERPRINT_MATCHER_KEY) and/or AllowedOrigins (your school website address) in fingerprint-helper.json.");
    return 1;
}

if (string.IsNullOrEmpty(builder.Configuration["Urls"]) && string.IsNullOrEmpty(Environment.GetEnvironmentVariable("ASPNETCORE_URLS")))
{
    builder.WebHost.UseUrls("http://127.0.0.1:5100");
}
// Fingerprint pictures are small; this stops anyone sending something huge.
builder.WebHost.ConfigureKestrel(o => o.Limits.MaxRequestBodySize = 20 * 1024 * 1024);

var app = builder.Build();

app.Use(async (context, next) =>
{
    var origin = context.Request.Headers.Origin.ToString().TrimEnd('/').ToLowerInvariant();

    if (origin != "")
    {
        // A web page is calling. Only the school's own website may, so other sites cannot use the reader's helper.
        if (!origins.Contains(origin))
        {
            context.Response.StatusCode = 403;
            return;
        }
        context.Response.Headers.AccessControlAllowOrigin = context.Request.Headers.Origin.ToString();
        context.Response.Headers.Vary = "Origin";
        context.Response.Headers.AccessControlAllowMethods = "GET, POST, OPTIONS";
        context.Response.Headers.AccessControlAllowHeaders = "Content-Type";
        context.Response.Headers.AccessControlMaxAge = "600";
        // Chrome and Edge ask before a public website may reach this computer.
        context.Response.Headers["Access-Control-Allow-Private-Network"] = "true";
        if (HttpMethods.IsOptions(context.Request.Method))
        {
            context.Response.StatusCode = 204;
            return;
        }
        await next();
        return;
    }

    var given = context.Request.Headers["X-Matcher-Key"].ToString();
    if (key.Length < 16 || !System.Security.Cryptography.CryptographicOperations.FixedTimeEquals(
            System.Text.Encoding.UTF8.GetBytes(given), System.Text.Encoding.UTF8.GetBytes(key)))
    {
        context.Response.StatusCode = 401;
        return;
    }
    await next();
});

app.MapGet("/health", () => Results.Ok(new { ok = true, version = 2 }));

// A picture becomes a template the system stores for that finger.
app.MapPost("/extract", (ExtractRequest request) =>
{
    try
    {
        var template = Read(request.Image, request.Dpi);
        return Results.Ok(new { template = Convert.ToBase64String(template.ToByteArray()) });
    }
    catch (Exception e) when (e is FormatException or ArgumentException or InvalidOperationException)
    {
        return Results.UnprocessableEntity(new { error = "Not a readable fingerprint picture." });
    }
});

// Finds the best match for a new picture among the templates sent. The score must reach the threshold.
app.MapPost("/identify", (IdentifyRequest request) =>
{
    FingerprintMatcher matcher;
    try
    {
        matcher = new FingerprintMatcher(Read(request.Image, request.Dpi));
    }
    catch (Exception e) when (e is FormatException or ArgumentException or InvalidOperationException)
    {
        return Results.UnprocessableEntity(new { error = "Not a readable fingerprint picture." });
    }

    string? best = null;
    double bestScore = 0;
    foreach (var candidate in request.Candidates ?? [])
    {
        double score;
        try
        {
            score = matcher.Match(new FingerprintTemplate(Convert.FromBase64String(candidate.Template)));
        }
        catch (Exception e) when (e is FormatException or ArgumentException or InvalidOperationException)
        {
            continue;
        }
        if (score > bestScore)
        {
            bestScore = score;
            best = candidate.Id;
        }
    }

    var threshold = request.Threshold > 0 ? request.Threshold : 40;
    return Results.Ok(new { id = bestScore >= threshold ? best : null, score = bestScore });
});

Console.WriteLine("Fingerprint helper is running. Keep this window open, or start it hidden at sign-in.");
app.Run();
return 0;

// The U.are.U 5100 scans at 500 dpi, the default here.
static FingerprintTemplate Read(string base64, double dpi) =>
    new(new FingerprintImage(Convert.FromBase64String(base64), new FingerprintImageOptions { Dpi = dpi > 0 ? dpi : 500 }));

record ExtractRequest(string Image, double Dpi);
record Candidate(string Id, string Template);
record IdentifyRequest(string Image, double Dpi, double Threshold, Candidate[]? Candidates);
