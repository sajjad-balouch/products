import java.util.Properties

plugins {
    id("com.android.application")
}

// MSK SMS Gateway: the owner's own Android phone sends schools' SMS from its SIM.
// It talks to {site}/sms-gateway with a gw_ token from owner panel → Messaging.
// Signed with the same upload key as the Sales app (android/keystore, never uploaded to the server).
val signing = Properties().apply {
    val f = rootProject.file("../android/keystore/keystore.properties")
    if (f.exists()) f.inputStream().use { load(it) }
}

android {
    namespace = "com.mskdevs.smsgateway"
    compileSdk { version = release(36) { minorApiLevel = 1 } }

    defaultConfig {
        applicationId = "com.mskdevs.smsgateway"
        minSdk = 24
        targetSdk = 36
        versionCode = 2
        versionName = "2.0"
    }

    signingConfigs {
        create("release") {
            if (signing.isNotEmpty()) {
                storeFile = rootProject.file("../android/keystore/" + signing.getProperty("storeFile"))
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
