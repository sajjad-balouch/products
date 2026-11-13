package com.mskdevs.smsgateway

import android.Manifest
import android.annotation.SuppressLint
import android.app.Activity
import android.content.BroadcastReceiver
import android.content.ClipboardManager
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Color
import android.graphics.Typeface
import android.graphics.drawable.GradientDrawable
import android.net.Uri
import android.os.BatteryManager
import android.os.Build
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.os.PowerManager
import android.provider.Settings
import android.text.InputType
import android.text.format.DateUtils
import android.view.Gravity
import android.view.View
import android.view.ViewGroup.LayoutParams.MATCH_PARENT
import android.view.ViewGroup.LayoutParams.WRAP_CONTENT
import android.widget.EditText
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.ScrollView
import android.widget.TextView
import android.widget.Toast

/**
 * One screen: the live state of the gateway, a big Start / Stop button, the server link and token
 * (or one "setup code" pasted from the owner panel), and a checklist of what the phone needs.
 */
class MainActivity : Activity() {

    private object C {
        val brand = Color.parseColor("#0A2F2A")
        val brand2 = Color.parseColor("#115E52")
        val accent = Color.parseColor("#10B981")
        val bg = Color.parseColor("#F1F5F4")
        val card = Color.WHITE
        val text = Color.parseColor("#0F172A")
        val muted = Color.parseColor("#64748B")
        val line = Color.parseColor("#E2E8F0")
        val green = Color.parseColor("#059669")
        val greenBg = Color.parseColor("#D1FAE5")
        val amber = Color.parseColor("#D97706")
        val amberBg = Color.parseColor("#FEF3C7")
        val red = Color.parseColor("#DC2626")
        val redBg = Color.parseColor("#FEE2E2")
        val grey = Color.parseColor("#94A3B8")
    }

    private lateinit var prefs: Prefs
    private lateinit var url: EditText
    private lateinit var token: EditText
    private lateinit var dot: View
    private lateinit var stateTitle: TextView
    private lateinit var stateDetail: TextView
    private lateinit var sentValue: TextView
    private lateinit var failedValue: TextView
    private lateinit var batteryValue: TextView
    private lateinit var lastCheck: TextView
    private lateinit var mainButton: TextView
    private lateinit var checks: LinearLayout
    private val handler = Handler(Looper.getMainLooper())
    private val refresh = object : Runnable {
        override fun run() {
            render()
            handler.postDelayed(this, 2000)
        }
    }

    private fun dp(v: Int) = (v * resources.displayMetrics.density).toInt()

    private fun rounded(color: Int, radius: Int = 18, stroke: Int? = null) = GradientDrawable().apply {
        setColor(color)
        cornerRadius = dp(radius).toFloat()
        if (stroke != null) setStroke(dp(1), stroke)
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        prefs = Prefs(this)
        window.statusBarColor = C.brand
        window.navigationBarColor = C.bg

        // The page takes the first focus, so the screen opens at the top instead of jumping to a text box.
        val page = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setBackgroundColor(C.bg)
            isFocusableInTouchMode = true
            descendantFocusability = android.view.ViewGroup.FOCUS_BEFORE_DESCENDANTS
            clipChildren = false
        }
        val head = header()
        page.addView(head)

        val body = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setPadding(dp(16), 0, dp(16), dp(24)); clipChildren = false; clipToPadding = false }
        body.addView(statusCard(), lp(top = -28))
        mainButton = TextView(this).apply {
            gravity = Gravity.CENTER
            textSize = 17f
            setTypeface(typeface, Typeface.BOLD)
            setTextColor(Color.WHITE)
            setPadding(0, dp(16), 0, dp(16))
            setOnClickListener { if (prefs.enabled) stop() else saveAndStart() }
        }
        body.addView(mainButton, lp(top = 14))
        body.addView(setupCard(), lp(top = 14))
        body.addView(checklistCard(), lp(top = 14))
        body.addView(tipsCard(), lp(top = 14))
        body.addView(TextView(this).apply {
            text = "MSK Devs · SMS Gateway 2.0"
            textSize = 12f
            setTextColor(C.grey)
            gravity = Gravity.CENTER
            setPadding(0, dp(18), 0, 0)
        })
        page.addView(body)

        val scroll = ScrollView(this).apply { setBackgroundColor(C.bg); addView(page) }
        // Android 15+ draws under the status and navigation bars: keep the header text and the last card clear of them.
        scroll.setOnApplyWindowInsetsListener { v, insets ->
            val (top, bottom, keyboard) = if (Build.VERSION.SDK_INT >= 30) {
                val bars = insets.getInsets(android.view.WindowInsets.Type.systemBars())
                Triple(bars.top, bars.bottom, insets.getInsets(android.view.WindowInsets.Type.ime()).bottom)
            } else {
                @Suppress("DEPRECATION") Triple(insets.systemWindowInsetTop, insets.systemWindowInsetBottom, 0)
            }
            head.setPadding(dp(20), dp(26) + top, dp(20), dp(52))
            body.setPadding(dp(16), 0, dp(16), dp(24) + bottom)
            // With the keyboard open the page ends above it, so the box being typed in stays in sight.
            v.setPadding(0, 0, 0, keyboard)
            if (keyboard > 0) v.post { currentFocus?.let { f -> (v as ScrollView).requestChildFocus(f, f) } }
            insets
        }
        setContentView(scroll)
        page.requestFocus()
    }

    private fun lp(top: Int = 0) = LinearLayout.LayoutParams(MATCH_PARENT, WRAP_CONTENT).apply { topMargin = dp(top) }

    private fun header(): View = LinearLayout(this).apply {
        orientation = LinearLayout.HORIZONTAL
        gravity = Gravity.CENTER_VERTICAL
        background = GradientDrawable(GradientDrawable.Orientation.TL_BR, intArrayOf(C.brand, C.brand2))
        setPadding(dp(20), dp(26), dp(20), dp(52))
        addView(ImageView(context).apply {
            setImageResource(R.drawable.ic_logo)
        }, LinearLayout.LayoutParams(dp(52), dp(52)))
        addView(LinearLayout(context).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(dp(14), 0, 0, 0)
            addView(TextView(context).apply {
                text = "MSK SMS Gateway"
                textSize = 22f
                setTypeface(typeface, Typeface.BOLD)
                setTextColor(Color.WHITE)
            })
            addView(TextView(context).apply {
                text = "Aap ka apna SMS system · fee aur qist ki yaad-dehani"
                textSize = 13f
                setTextColor(Color.parseColor("#A7F3D0"))
            })
        })
    }

    private fun card(): LinearLayout = LinearLayout(this).apply {
        orientation = LinearLayout.VERTICAL
        background = rounded(C.card, 20, C.line)
        elevation = dp(2).toFloat()
        setPadding(dp(18), dp(18), dp(18), dp(18))
    }

    private fun title(text: String) = TextView(this).apply {
        this.text = text
        textSize = 16f
        setTypeface(typeface, Typeface.BOLD)
        setTextColor(C.text)
    }

    private fun statusCard(): View = card().apply {
        addView(LinearLayout(context).apply {
            orientation = LinearLayout.HORIZONTAL
            gravity = Gravity.CENTER_VERTICAL
            dot = View(context)
            addView(dot, LinearLayout.LayoutParams(dp(14), dp(14)))
            stateTitle = TextView(context).apply {
                textSize = 20f
                setTypeface(typeface, Typeface.BOLD)
                setTextColor(C.text)
                setPadding(dp(10), 0, 0, 0)
            }
            addView(stateTitle)
        })
        stateDetail = TextView(context).apply { textSize = 13f; setTextColor(C.muted); setPadding(0, dp(6), 0, 0) }
        addView(stateDetail)

        addView(LinearLayout(context).apply {
            orientation = LinearLayout.HORIZONTAL
            sentValue = stat(this, "Aaj gaye", C.green, C.greenBg)
            failedValue = stat(this, "Nakaam", C.red, C.redBg, gap = true)
            batteryValue = stat(this, "Battery", C.brand2, Color.parseColor("#E0F2F1"), gap = true)
        }, lp(top = 14))
        lastCheck = TextView(context).apply { textSize = 12f; setTextColor(C.grey); setPadding(0, dp(10), 0, 0) }
        addView(lastCheck)
    }

    private fun stat(row: LinearLayout, label: String, color: Int, bg: Int, gap: Boolean = false): TextView {
        val value = TextView(this).apply {
            textSize = 22f
            setTypeface(typeface, Typeface.BOLD)
            setTextColor(color)
            gravity = Gravity.CENTER
        }
        row.addView(LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            gravity = Gravity.CENTER
            background = rounded(bg, 14)
            setPadding(0, dp(10), 0, dp(10))
            addView(value)
            addView(TextView(context).apply { text = label; textSize = 12f; setTextColor(C.muted); gravity = Gravity.CENTER })
        }, LinearLayout.LayoutParams(0, WRAP_CONTENT, 1f).apply { if (gap) marginStart = dp(8) })
        return value
    }

    private fun field(label: String, hint: String, value: String, secret: Boolean): Pair<View, EditText> {
        val input = EditText(this).apply {
            this.hint = hint
            setText(value)
            textSize = 15f
            setTextColor(C.text)
            setHintTextColor(C.grey)
            background = rounded(Color.parseColor("#F8FAFC"), 12, C.line)
            setPadding(dp(14), dp(12), dp(14), dp(12))
            inputType = if (secret) InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_PASSWORD else InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_URI
            isSingleLine = true
            if (secret) transformationMethod = android.text.method.PasswordTransformationMethod.getInstance()
        }
        val box = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            addView(TextView(context).apply { text = label; textSize = 13f; setTextColor(C.muted); setPadding(0, 0, 0, dp(6)) })
            addView(input, LinearLayout.LayoutParams(MATCH_PARENT, WRAP_CONTENT))
        }
        return box to input
    }

    private fun smallButton(text: String, filled: Boolean, onClick: () -> Unit) = TextView(this).apply {
        this.text = text
        gravity = Gravity.CENTER
        textSize = 14f
        setTypeface(typeface, Typeface.BOLD)
        setTextColor(if (filled) Color.WHITE else C.brand2)
        background = if (filled) rounded(C.brand2, 12) else rounded(Color.WHITE, 12, C.brand2)
        setPadding(dp(14), dp(12), dp(14), dp(12))
        setOnClickListener { onClick() }
    }

    private fun setupCard(): View = card().apply {
        addView(title("Server se jorein"))
        addView(TextView(context).apply {
            text = "Owner panel → Messaging → Gateway phones mein phone add karein, phir setup code copy karke yahan paste karein."
            textSize = 13f
            setTextColor(C.muted)
            setPadding(0, dp(4), 0, dp(12))
        })
        addView(smallButton("Setup code paste karein", true) { pasteSetupCode() }, lp())
        val (urlBox, urlInput) = field("Server link", "https://products.assanzameen.com/sms-gateway", prefs.url, false)
        url = urlInput
        addView(urlBox, lp(top = 14))
        val (tokenBox, tokenInput) = field("Phone ka token", "gw_…", prefs.token, true)
        token = tokenInput
        addView(tokenBox, lp(top = 10))
        addView(smallButton("Mehfooz karein", false) { if (saveFields()) Toast.makeText(this@MainActivity, "Mehfooz ho gaya", Toast.LENGTH_SHORT).show() }, lp(top = 12))
    }

    private fun checklistCard(): View = card().apply {
        addView(title("Phone ki tayyari"))
        checks = LinearLayout(context).apply { orientation = LinearLayout.VERTICAL; setPadding(0, dp(6), 0, 0) }
        addView(checks)
    }

    private fun tipsCard(): View = card().apply {
        background = rounded(Color.parseColor("#ECFDF5"), 20, Color.parseColor("#A7F3D0"))
        addView(title("Zaroori baatein"))
        listOf(
            "Phone charger par lagaye rakhein, WiFi ya data on rahe.",
            "SIM par SMS package lagwa lein.",
            "Aik SIM se rozana chand sau SMS se zyada na bhejein, warna SIM block ho sakti hai. Zyada chahiyen to doosra phone jor dein.",
            "Xiaomi, Oppo, Vivo, Infinix par Settings mein app ka Autostart on karein.",
        ).forEach { tip ->
            addView(TextView(context).apply {
                text = "•  $tip"
                textSize = 13f
                setTextColor(Color.parseColor("#065F46"))
                setPadding(0, dp(6), 0, 0)
            })
        }
    }

    private fun checkRow(ok: Boolean, label: String, fix: String?, onFix: (() -> Unit)?) = LinearLayout(this).apply {
        orientation = LinearLayout.HORIZONTAL
        gravity = Gravity.CENTER_VERTICAL
        setPadding(0, dp(8), 0, dp(8))
        addView(TextView(context).apply {
            text = if (ok) "✓" else "!"
            gravity = Gravity.CENTER
            textSize = 13f
            setTypeface(typeface, Typeface.BOLD)
            setTextColor(if (ok) C.green else C.amber)
            background = GradientDrawable().apply { shape = GradientDrawable.OVAL; setColor(if (ok) C.greenBg else C.amberBg) }
        }, LinearLayout.LayoutParams(dp(26), dp(26)))
        addView(TextView(context).apply { text = label; textSize = 14f; setTextColor(C.text); setPadding(dp(12), 0, 0, 0) }, LinearLayout.LayoutParams(0, WRAP_CONTENT, 1f))
        if (!ok && fix != null && onFix != null) {
            addView(TextView(context).apply {
                text = fix
                textSize = 13f
                setTypeface(typeface, Typeface.BOLD)
                setTextColor(C.brand2)
                setPadding(dp(10), dp(6), dp(4), dp(6))
                setOnClickListener { onFix() }
            })
        }
    }

    private fun render() {
        val on = prefs.enabled
        val reachable = prefs.online && System.currentTimeMillis() - prefs.lastOk < 3 * 60_000
        val (color, label) = when {
            !on -> C.grey to "Band hai"
            reachable -> C.green to "Chalu hai"
            else -> C.amber to "Server se rabta nahi"
        }
        dot.background = GradientDrawable().apply { shape = GradientDrawable.OVAL; setColor(color) }
        stateTitle.text = label
        stateDetail.text = if (on) prefs.status else "Start dabayein to SMS bhejna shuru ho jayega."
        sentValue.text = prefs.sentToday.toString()
        failedValue.text = prefs.failedToday.toString()
        batteryValue.text = battery()?.let { "$it%" } ?: "—"
        lastCheck.text = if (prefs.lastOk > 0) "Server se aakhri rabta: " + DateUtils.getRelativeTimeSpanString(prefs.lastOk, System.currentTimeMillis(), DateUtils.SECOND_IN_MILLIS) else "Abhi tak server se rabta nahi hua"

        mainButton.text = if (on) "■  Band karein" else "▶  Start karein"
        mainButton.background = rounded(if (on) C.red else C.accent, 16)

        checks.removeAllViews()
        val pm = getSystemService(POWER_SERVICE) as PowerManager
        checks.addView(checkRow(prefs.url.isNotBlank() && prefs.token.isNotBlank(), "Server link aur token", null, null))
        checks.addView(checkRow(granted(Manifest.permission.SEND_SMS), "SMS bhejne ki ijazat", "Ijazat dein") { askPermissions() })
        if (Build.VERSION.SDK_INT >= 33) checks.addView(checkRow(granted(Manifest.permission.POST_NOTIFICATIONS), "Notification ki ijazat", "Ijazat dein") { askPermissions() })
        checks.addView(checkRow(pm.isIgnoringBatteryOptimizations(packageName), "Battery saver se azad", "Theek karein") { askBatteryExemption() })
    }

    override fun onResume() {
        super.onResume()
        handler.post(refresh)
    }

    override fun onPause() {
        handler.removeCallbacks(refresh)
        super.onPause()
    }

    private fun granted(p: String) = checkSelfPermission(p) == PackageManager.PERMISSION_GRANTED

    private fun battery(): Int? = runCatching {
        (getSystemService(BATTERY_SERVICE) as BatteryManager).getIntProperty(BatteryManager.BATTERY_PROPERTY_CAPACITY)
    }.getOrNull()?.takeIf { it in 0..100 }

    /** The owner panel gives one code: MSK1|<server link>|<token> */
    private fun pasteSetupCode() {
        val clip = (getSystemService(CLIPBOARD_SERVICE) as ClipboardManager).primaryClip
        val text = clip?.takeIf { it.itemCount > 0 }?.getItemAt(0)?.coerceToText(this)?.toString()?.trim().orEmpty()
        val parts = text.split("|")
        if (parts.size == 3 && parts[0] == "MSK1" && parts[2].startsWith("gw_")) {
            url.setText(parts[1])
            token.setText(parts[2])
            if (saveFields()) Toast.makeText(this, "Setup ho gaya. Ab Start dabayein.", Toast.LENGTH_LONG).show()
        } else {
            Toast.makeText(this, "Clipboard mein setup code nahi mila. Owner panel se \"Copy setup code\" dabayein.", Toast.LENGTH_LONG).show()
        }
    }

    private fun saveFields(): Boolean {
        val u = url.text.toString().trim()
        if (!u.startsWith("http://") && !u.startsWith("https://")) {
            Toast.makeText(this, "Server link https:// se shuru ho", Toast.LENGTH_LONG).show()
            return false
        }
        if (!token.text.toString().trim().startsWith("gw_")) {
            Toast.makeText(this, "Token gw_ se shuru hota hai", Toast.LENGTH_LONG).show()
            return false
        }
        prefs.url = u
        prefs.token = token.text.toString()
        return true
    }

    private fun saveAndStart() {
        if (!saveFields()) return
        if (!askPermissions()) start()
    }

    /** @return true when a permission dialog was opened */
    private fun askPermissions(): Boolean {
        val needed = buildList {
            add(Manifest.permission.SEND_SMS)
            if (Build.VERSION.SDK_INT >= 33) add(Manifest.permission.POST_NOTIFICATIONS)
        }.filter { !granted(it) }
        if (needed.isEmpty()) return false
        requestPermissions(needed.toTypedArray(), 1)
        return true
    }

    override fun onRequestPermissionsResult(requestCode: Int, permissions: Array<out String>, grantResults: IntArray) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        if (!granted(Manifest.permission.SEND_SMS)) {
            Toast.makeText(this, "SMS ki ijazat ke baghair gateway nahi chal sakta", Toast.LENGTH_LONG).show()
        } else if (!prefs.enabled && prefs.url.isNotBlank() && prefs.token.isNotBlank()) {
            start()
        }
        render()
    }

    private fun start() {
        prefs.enabled = true
        GatewayService.start(this)
        Toast.makeText(this, "Gateway chalu", Toast.LENGTH_SHORT).show()
        render()
    }

    private fun stop() {
        prefs.enabled = false
        startService(Intent(this, GatewayService::class.java).setAction(GatewayService.ACTION_STOP))
        render()
    }

    @SuppressLint("BatteryLife")
    private fun askBatteryExemption() {
        val pm = getSystemService(POWER_SERVICE) as PowerManager
        if (!pm.isIgnoringBatteryOptimizations(packageName)) {
            startActivity(Intent(Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS, Uri.parse("package:$packageName")))
        }
    }
}

/** When the phone restarts, the gateway starts again by itself. */
class BootReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        if (intent.action == Intent.ACTION_BOOT_COMPLETED && Prefs(context).enabled) {
            GatewayService.start(context)
        }
    }
}
