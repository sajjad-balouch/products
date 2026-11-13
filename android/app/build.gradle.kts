import java.util.Properties

plugins {
    id("com.android.application")
}

// MSK Devs Sales: a Trusted Web Activity. The Play Store app opens the web app at /sales
// full screen in Chrome, so offline orders and notifications work exactly as on the web.
val site = "https://products.assanzameen.com"

val signing = Properties().apply {
    val f = rootProject.file("keystore/keystore.properties")
    if (f.exists()) f.inputStream().use { load(it) }
}

android {
    namespace = "com.mskdevs.sales"
    buildFeatures { resValues = true }
    compileSdk { version = release(36) { minorApiLevel = 1 } }

    defaultConfig {
        applicationId = "com.mskdevs.sales"
        minSdk = 23
        targetSdk = 36
        versionCode = 1
        versionName = "1.0"
        resValue("string", "launch_url", "$site/sales")
        resValue("string", "site_host", site.removePrefix("https://"))
        resValue("string", "asset_statements", "[{\\\"relation\\\": [\\\"delegate_permission/common.handle_all_urls\\\"], \\\"target\\\": {\\\"namespace\\\": \\\"web\\\", \\\"site\\\": \\\"$site\\\"}}]")
    }

    signingConfigs {
        create("release") {
            if (signing.isNotEmpty()) {
                storeFile = rootProject.file("keystore/" + signing.getProperty("storeFile"))
                storePassword = signing.getProperty("storePassword")
                keyAlias = signing.getProperty("keyAlias")
                keyPassword = signing.getProperty("keyPassword")
            }
        }
    }

    buildTypes {
        release {
            isMinifyEnabled = false
            signingConfig = signingConfigs.getByName("release")
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
}

dependencies {
    implementation("com.google.androidbrowserhelper:androidbrowserhelper:2.6.2")
}
