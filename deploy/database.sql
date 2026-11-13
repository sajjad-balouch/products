-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: darsgah_deploy
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `academic_sessions`
--

DROP TABLE IF EXISTS `academic_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_sessions_school_id_foreign` (`school_id`),
  CONSTRAINT `academic_sessions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_sessions`
--

LOCK TABLES `academic_sessions` WRITE;
/*!40000 ALTER TABLE `academic_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `academic_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `published_on` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_school_id_foreign` (`school_id`),
  CONSTRAINT `announcements_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendances`
--

DROP TABLE IF EXISTS `attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `section_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `status` char(1) NOT NULL,
  `marked_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendances_student_id_date_unique` (`student_id`,`date`),
  KEY `attendances_marked_by_foreign` (`marked_by`),
  KEY `attendances_section_id_date_index` (`section_id`,`date`),
  KEY `attendances_school_id_date_index` (`school_id`,`date`),
  CONSTRAINT `attendances_marked_by_foreign` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendances_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendances_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendances_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendances`
--

LOCK TABLES `attendances` WRITE;
/*!40000 ALTER TABLE `attendances` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes`)),
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_school_id_created_at_index` (`school_id`,`created_at`),
  KEY `audit_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  CONSTRAINT `audit_logs_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,NULL,NULL,'System','created','Site',1,NULL,'{\"old\":null,\"new\":{\"owner_key\":\"platform\",\"is_published\":true,\"settings\":\"{\\\"design\\\":{\\\"site_name\\\":\\\"MSK Devs\\\",\\\"primary\\\":\\\"#4f46e5\\\",\\\"accent\\\":\\\"#f97316\\\",\\\"seo_description\\\":\\\"School and college management: fees, attendance, results, timetable and a parent portal in one system.\\\"},\\\"header\\\":{\\\"cta_show\\\":true,\\\"cta_label\\\":\\\"Start free trial\\\",\\\"cta_url\\\":\\\"\\\\\\/signup\\\"},\\\"footer\\\":{\\\"about\\\":\\\"School and college management software.\\\"}}\",\"id\":1}}','127.0.0.1','2026-11-04 10:48:44'),(2,NULL,NULL,'System','created','SitePage',1,NULL,'{\"old\":null,\"new\":{\"title\":\"Home\",\"slug\":\"home\",\"is_home\":true,\"sort\":1,\"site_id\":1,\"id\":1}}','127.0.0.1','2026-11-04 10:48:44'),(3,NULL,NULL,'System','created','SiteSection',1,NULL,'{\"old\":null,\"new\":{\"type\":\"hero\",\"data\":\"{\\\"_anchor\\\":\\\"top\\\",\\\"_eyebrow\\\":\\\"Made for schools and colleges in Pakistan\\\",\\\"layout\\\":\\\"product\\\",\\\"heading\\\":\\\"Run your whole school from one simple system\\\",\\\"highlight\\\":\\\"one simple system\\\",\\\"text\\\":\\\"Fees, attendance, results, timetable and parents in one place. MSK Devs does the paperwork, so your staff can spend their time on students.\\\",\\\"button_label\\\":\\\"Start free trial\\\",\\\"button_url\\\":\\\"\\\\\\/signup\\\",\\\"button2_label\\\":\\\"See how it works\\\",\\\"button2_url\\\":\\\"\\\\\\/#how\\\",\\\"chips\\\":\\\"30-day free trial\\\\nEnglish and Urdu\\\\nWorks on any phone\\\\nEach school\'s data kept apart\\\",\\\"_bg\\\":\\\"dark\\\",\\\"_spacing\\\":\\\"large\\\"}\",\"sort\":1,\"site_page_id\":1,\"id\":1}}','127.0.0.1','2026-11-04 10:48:44'),(4,NULL,NULL,'System','created','SiteSection',2,NULL,'{\"old\":null,\"new\":{\"type\":\"features\",\"data\":\"{\\\"_anchor\\\":\\\"features\\\",\\\"_eyebrow\\\":\\\"Everything in one place\\\",\\\"heading\\\":\\\"One system instead of five registers\\\",\\\"intro\\\":\\\"Everything a school office does every day, in screens your staff learn in an afternoon.\\\",\\\"style\\\":\\\"bento\\\",\\\"columns\\\":\\\"3\\\",\\\"items\\\":[{\\\"image\\\":null,\\\"title\\\":\\\"Fee vouchers in one click\\\",\\\"text\\\":\\\"Vouchers for every family, late fees and sibling discounts worked out for you, and a list of who still owes.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Attendance in seconds\\\",\\\"text\\\":\\\"Everyone starts present. Tap the absent ones, and parents get a WhatsApp message.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Parents on their phone\\\",\\\"text\\\":\\\"Attendance, fees, results and homework, in English or Urdu.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Every student in one place\\\",\\\"text\\\":\\\"Families, siblings, classes and history. Bring them in from Excel.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Results and report cards\\\",\\\"text\\\":\\\"Marks by subject, positions and printed report cards.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"A website for every school\\\",\\\"text\\\":\\\"Picture slider, campuses, gallery and an online admission form.\\\",\\\"link\\\":\\\"\\\"}]}\",\"sort\":2,\"site_page_id\":1,\"id\":2}}','127.0.0.1','2026-11-04 10:48:44'),(5,NULL,NULL,'System','created','SiteSection',3,NULL,'{\"old\":null,\"new\":{\"type\":\"showcase\",\"data\":\"{\\\"_anchor\\\":\\\"timetable\\\",\\\"_eyebrow\\\":\\\"Timetable\\\",\\\"heading\\\":\\\"Drag, drop, done\\\",\\\"intro\\\":\\\"Build the timetable by dragging subjects onto the week. A teacher can never be put in two classes at once, and the system suggests what fits.\\\",\\\"address\\\":\\\"timetable\\\",\\\"_bg\\\":\\\"tint\\\"}\",\"sort\":3,\"site_page_id\":1,\"id\":3}}','127.0.0.1','2026-11-04 10:48:44'),(6,NULL,NULL,'System','created','SiteSection',4,NULL,'{\"old\":null,\"new\":{\"type\":\"steps\",\"data\":\"{\\\"_anchor\\\":\\\"how\\\",\\\"_eyebrow\\\":\\\"How it works\\\",\\\"heading\\\":\\\"Up and running in a day\\\",\\\"items\\\":[{\\\"title\\\":\\\"Sign up\\\",\\\"text\\\":\\\"Your school is ready in a minute, with a 30-day free trial.\\\"},{\\\"title\\\":\\\"Bring in your students\\\",\\\"text\\\":\\\"Add classes, then import students and parents from Excel.\\\"},{\\\"title\\\":\\\"Send the first vouchers\\\",\\\"text\\\":\\\"Generate fee vouchers and invite parents to their portal.\\\"}]}\",\"sort\":4,\"site_page_id\":1,\"id\":4}}','127.0.0.1','2026-11-04 10:48:44'),(7,NULL,NULL,'System','created','SiteSection',5,NULL,'{\"old\":null,\"new\":{\"type\":\"image_text\",\"data\":\"{\\\"_anchor\\\":\\\"parents\\\",\\\"_eyebrow\\\":\\\"For parents\\\",\\\"heading\\\":\\\"Parents always know how their child is doing\\\",\\\"body\\\":\\\"Attendance, results, fees and the daily diary in one portal, in English or Urdu. Absent alerts reach parents the same morning.\\\",\\\"image_side\\\":\\\"left\\\",\\\"highlights\\\":\\\"Parent portal\\\\nUrdu and English\\\\nAbsent alerts\\\\nReport cards\\\",\\\"_bg\\\":\\\"tint\\\"}\",\"sort\":5,\"site_page_id\":1,\"id\":5}}','127.0.0.1','2026-11-04 10:48:44'),(8,NULL,NULL,'System','created','SiteSection',6,NULL,'{\"old\":null,\"new\":{\"type\":\"plans\",\"data\":\"{\\\"_anchor\\\":\\\"pricing\\\",\\\"_eyebrow\\\":\\\"Pricing\\\",\\\"heading\\\":\\\"Simple monthly plans\\\",\\\"intro\\\":\\\"Start with a free 30-day trial. Choose a plan when you are ready.\\\",\\\"note\\\":\\\"Prices in Pakistani rupees. Ask us about a yearly price or more than one campus.\\\",\\\"button_label\\\":\\\"Start free trial\\\",\\\"button_url\\\":\\\"\\\\\\/signup\\\"}\",\"sort\":6,\"site_page_id\":1,\"id\":6}}','127.0.0.1','2026-11-04 10:48:44'),(9,NULL,NULL,'System','created','SiteSection',7,NULL,'{\"old\":null,\"new\":{\"type\":\"faq\",\"data\":\"{\\\"_anchor\\\":\\\"faq\\\",\\\"_eyebrow\\\":\\\"Questions\\\",\\\"heading\\\":\\\"Questions schools ask\\\",\\\"items\\\":[{\\\"question\\\":\\\"Do we need to install anything?\\\",\\\"answer\\\":\\\"No. It runs in the browser on a computer or a phone.\\\"},{\\\"question\\\":\\\"Does it work in Urdu?\\\",\\\"answer\\\":\\\"Yes. Parents can use their portal in Urdu or English.\\\"},{\\\"question\\\":\\\"Can parents pay fees online?\\\",\\\"answer\\\":\\\"Parents pay by bank, JazzCash or Easypaisa and upload a picture of the payment. The office checks it and marks the voucher paid.\\\"},{\\\"question\\\":\\\"Can other schools see our data?\\\",\\\"answer\\\":\\\"No. Every school only ever sees its own students, staff and fees.\\\"},{\\\"question\\\":\\\"What happens after the free trial?\\\",\\\"answer\\\":\\\"Choose a plan to keep going. Your data stays exactly as you left it.\\\"}]}\",\"sort\":7,\"site_page_id\":1,\"id\":7}}','127.0.0.1','2026-11-04 10:48:44'),(10,NULL,NULL,'System','created','SiteSection',8,NULL,'{\"old\":null,\"new\":{\"type\":\"cta\",\"data\":\"{\\\"_eyebrow\\\":\\\"30-day free trial\\\",\\\"heading\\\":\\\"Give your school office its time back\\\",\\\"text\\\":\\\"Sign up and your school is ready in a minute. No card, no installation.\\\",\\\"button_label\\\":\\\"Start free trial\\\",\\\"button_url\\\":\\\"\\\\\\/signup\\\",\\\"_bg\\\":\\\"brand\\\",\\\"_spacing\\\":\\\"large\\\"}\",\"sort\":8,\"site_page_id\":1,\"id\":8}}','127.0.0.1','2026-11-04 10:48:44'),(11,NULL,NULL,'System','created','SitePage',2,NULL,'{\"old\":null,\"new\":{\"title\":\"All features\",\"slug\":\"features\",\"is_home\":false,\"sort\":2,\"site_id\":1,\"id\":2}}','127.0.0.1','2026-11-04 10:48:44'),(12,NULL,NULL,'System','created','SiteSection',9,NULL,'{\"old\":null,\"new\":{\"type\":\"hero\",\"data\":\"{\\\"layout\\\":\\\"center\\\",\\\"_eyebrow\\\":\\\"Features\\\",\\\"heading\\\":\\\"Everything your school needs\\\",\\\"text\\\":\\\"Each role sees only what it needs: the principal, the accountant, teachers and parents.\\\",\\\"_bg\\\":\\\"tint\\\"}\",\"sort\":1,\"site_page_id\":2,\"id\":9}}','127.0.0.1','2026-11-04 10:48:44'),(13,NULL,NULL,'System','created','SiteSection',10,NULL,'{\"old\":null,\"new\":{\"type\":\"features\",\"data\":\"{\\\"columns\\\":\\\"3\\\",\\\"items\\\":[{\\\"image\\\":null,\\\"title\\\":\\\"Fees\\\",\\\"text\\\":\\\"Monthly vouchers, late fees, sibling discounts, payment proofs from parents and daily cash closing.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Attendance\\\",\\\"text\\\":\\\"Mark a class in seconds and queue WhatsApp messages to parents of absent students.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Exams and results\\\",\\\"text\\\":\\\"Enter marks by subject, publish results and print report cards.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Timetable\\\",\\\"text\\\":\\\"Drag and drop periods, with teacher clash checks and suggestions for what fits.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Admissions\\\",\\\"text\\\":\\\"A public apply form and a pipeline from enquiry to admission.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Early warning\\\",\\\"text\\\":\\\"Flags students at risk from attendance and results, with readable reasons.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Parent portal\\\",\\\"text\\\":\\\"Parents see attendance, results and fees, in English or Urdu.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Certificates and ID cards\\\",\\\"text\\\":\\\"Printed documents with a QR code anyone can verify.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Staff and payroll\\\",\\\"text\\\":\\\"Staff records, attendance, salary runs and salary slips.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Library and transport\\\",\\\"text\\\":\\\"Book loans, transport routes and which student uses them.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Finance\\\",\\\"text\\\":\\\"Expenses and an income and expense report.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Public website\\\",\\\"text\\\":\\\"Every school gets its own website, managed from its admin panel.\\\",\\\"link\\\":\\\"\\\"}]}\",\"sort\":2,\"site_page_id\":2,\"id\":10}}','127.0.0.1','2026-11-04 10:48:44'),(14,NULL,NULL,'System','created','SiteSection',11,NULL,'{\"old\":null,\"new\":{\"type\":\"cta\",\"data\":\"{\\\"_eyebrow\\\":\\\"30-day free trial\\\",\\\"heading\\\":\\\"Give your school office its time back\\\",\\\"text\\\":\\\"Sign up and your school is ready in a minute. No card, no installation.\\\",\\\"button_label\\\":\\\"Start free trial\\\",\\\"button_url\\\":\\\"\\\\\\/signup\\\",\\\"_bg\\\":\\\"brand\\\",\\\"_spacing\\\":\\\"large\\\"}\",\"sort\":3,\"site_page_id\":2,\"id\":11}}','127.0.0.1','2026-11-04 10:48:44'),(15,NULL,NULL,'System','created','SitePage',3,NULL,'{\"old\":null,\"new\":{\"title\":\"Contact\",\"slug\":\"contact\",\"is_home\":false,\"sort\":3,\"site_id\":1,\"id\":3}}','127.0.0.1','2026-11-04 10:48:44'),(16,NULL,NULL,'System','created','SiteSection',12,NULL,'{\"old\":null,\"new\":{\"type\":\"contact\",\"data\":\"{\\\"heading\\\":\\\"Talk to us\\\",\\\"text\\\":\\\"Send a message and we will get back to you, usually the same day.\\\",\\\"show_form\\\":true,\\\"show_details\\\":true,\\\"show_map\\\":false}\",\"sort\":1,\"site_page_id\":3,\"id\":12}}','127.0.0.1','2026-11-04 10:48:44'),(17,NULL,NULL,'System','created','SiteMenuItem',1,NULL,'{\"old\":null,\"new\":{\"location\":\"header\",\"label\":\"Features\",\"sort\":1,\"url\":\"\\/#features\",\"site_id\":1,\"id\":1}}','127.0.0.1','2026-11-04 10:48:44'),(18,NULL,NULL,'System','created','SiteMenuItem',2,NULL,'{\"old\":null,\"new\":{\"location\":\"header\",\"label\":\"How it works\",\"sort\":2,\"url\":\"\\/#how\",\"site_id\":1,\"id\":2}}','127.0.0.1','2026-11-04 10:48:44'),(19,NULL,NULL,'System','created','SiteMenuItem',3,NULL,'{\"old\":null,\"new\":{\"location\":\"header\",\"label\":\"Pricing\",\"sort\":3,\"url\":\"\\/#pricing\",\"site_id\":1,\"id\":3}}','127.0.0.1','2026-11-04 10:48:45'),(20,NULL,NULL,'System','created','SiteMenuItem',4,NULL,'{\"old\":null,\"new\":{\"location\":\"header\",\"label\":\"Questions\",\"sort\":4,\"url\":\"\\/#faq\",\"site_id\":1,\"id\":4}}','127.0.0.1','2026-11-04 10:48:45'),(21,NULL,NULL,'System','created','SiteMenuItem',5,NULL,'{\"old\":null,\"new\":{\"location\":\"header\",\"label\":\"Contact\",\"sort\":5,\"site_page_id\":3,\"site_id\":1,\"id\":5}}','127.0.0.1','2026-11-04 10:48:45'),(22,NULL,NULL,'System','created','SiteMenuItem',6,NULL,'{\"old\":null,\"new\":{\"location\":\"footer\",\"label\":\"All features\",\"sort\":1,\"site_page_id\":2,\"site_id\":1,\"id\":6}}','127.0.0.1','2026-11-04 10:48:45'),(23,NULL,NULL,'System','created','SiteMenuItem',7,NULL,'{\"old\":null,\"new\":{\"location\":\"footer\",\"label\":\"Pricing\",\"sort\":2,\"url\":\"\\/#pricing\",\"site_id\":1,\"id\":7}}','127.0.0.1','2026-11-04 10:48:45'),(24,NULL,NULL,'System','created','SiteMenuItem',8,NULL,'{\"old\":null,\"new\":{\"location\":\"footer\",\"label\":\"Contact\",\"sort\":3,\"site_page_id\":3,\"site_id\":1,\"id\":8}}','127.0.0.1','2026-11-04 10:48:45'),(25,NULL,NULL,'System','created','SiteMenuItem',9,NULL,'{\"old\":null,\"new\":{\"location\":\"footer\",\"label\":\"Sign in\",\"sort\":4,\"url\":\"\\/login\",\"site_id\":1,\"id\":9}}','127.0.0.1','2026-11-04 10:48:45'),(26,NULL,NULL,'System','created','SitePage',4,NULL,'{\"old\":null,\"new\":{\"title\":\"Installments & accounts\",\"slug\":\"installments\",\"is_home\":false,\"sort\":4,\"site_id\":1,\"id\":4}}','127.0.0.1','2026-11-04 10:48:45'),(27,NULL,NULL,'System','created','SiteSection',13,NULL,'{\"old\":null,\"new\":{\"type\":\"hero\",\"data\":\"{\\\"_anchor\\\":\\\"top\\\",\\\"_eyebrow\\\":\\\"For any business that sells on installments\\\",\\\"layout\\\":\\\"product\\\",\\\"heading\\\":\\\"Installment sales and accounts, fully under control\\\",\\\"highlight\\\":\\\"fully under control\\\",\\\"text\\\":\\\"Sell motorcycles, phones, appliances, furniture or anything else on qist. Every installment, late customer, receipt and rupee in your books, in one simple system.\\\",\\\"button_label\\\":\\\"Start free trial\\\",\\\"button_url\\\":\\\"\\\\\\/signup?product=business\\\",\\\"button2_label\\\":\\\"See what it does\\\",\\\"button2_url\\\":\\\"\\\\\\/p\\\\\\/installments#features\\\",\\\"chips\\\":\\\"Any item, any plan\\\\nDouble-entry accounts\\\\nWhatsApp reminders\\\\nWorks on any phone\\\",\\\"_bg\\\":\\\"dark\\\",\\\"_spacing\\\":\\\"large\\\"}\",\"sort\":1,\"site_page_id\":4,\"id\":13}}','127.0.0.1','2026-11-04 10:48:45'),(28,NULL,NULL,'System','created','SiteSection',14,NULL,'{\"old\":null,\"new\":{\"type\":\"features\",\"data\":\"{\\\"_anchor\\\":\\\"features\\\",\\\"_eyebrow\\\":\\\"Everything in one place\\\",\\\"heading\\\":\\\"From the sale to the last installment\\\",\\\"intro\\\":\\\"The way installment businesses already work, without the registers and the guesswork.\\\",\\\"style\\\":\\\"bento\\\",\\\"columns\\\":\\\"3\\\",\\\"items\\\":[{\\\"image\\\":null,\\\"title\\\":\\\"A sale in one minute\\\",\\\"text\\\":\\\"Cash price, markup, down payment and number of installments. The schedule and the monthly amount appear as you type.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Know who is late\\\",\\\"text\\\":\\\"Due today, late 1\\\\u201330, 31\\\\u201360, 61\\\\u201390 and over 90 days, with the amount and the days. Remind them or their guarantor on WhatsApp in one tap.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Receipts and agreements\\\",\\\"text\\\":\\\"Printed receipts for every payment, a customer statement, and an installment agreement with guarantors and terms, ready to sign.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Real accounts, done for you\\\",\\\"text\\\":\\\"Every sale and payment posts double-entry to your books. Markup counts as income only as it is paid, the way accountants do it.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Profit, balance sheet and more\\\",\\\"text\\\":\\\"Profit and loss, balance sheet, trial balance, ledgers, day book, collections and an aging of dues, at any date.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Guarantors and blacklist\\\",\\\"text\\\":\\\"Up to two guarantors on every sale, and a warning before selling again to a customer who did not pay.\\\",\\\"link\\\":\\\"\\\"}]}\",\"sort\":2,\"site_page_id\":4,\"id\":14}}','127.0.0.1','2026-11-04 10:48:45'),(29,NULL,NULL,'System','created','SiteSection',15,NULL,'{\"old\":null,\"new\":{\"type\":\"steps\",\"data\":\"{\\\"_anchor\\\":\\\"how\\\",\\\"_eyebrow\\\":\\\"How it works\\\",\\\"heading\\\":\\\"Ready the same day\\\",\\\"items\\\":[{\\\"title\\\":\\\"Sign up\\\",\\\"text\\\":\\\"Choose \\\\\\\"Shop or business\\\\\\\". Your chart of accounts is set up for you.\\\"},{\\\"title\\\":\\\"Set your terms\\\",\\\"text\\\":\\\"Your usual markup, late fee, grace days and agreement terms.\\\"},{\\\"title\\\":\\\"Sell and collect\\\",\\\"text\\\":\\\"Make sales, take installments at the counter, and follow up the late ones.\\\"}],\\\"_bg\\\":\\\"tint\\\"}\",\"sort\":3,\"site_page_id\":4,\"id\":15}}','127.0.0.1','2026-11-04 10:48:45'),(30,NULL,NULL,'System','created','SiteSection',16,NULL,'{\"old\":null,\"new\":{\"type\":\"features\",\"data\":\"{\\\"_eyebrow\\\":\\\"All features\\\",\\\"heading\\\":\\\"Everything an installment business needs\\\",\\\"columns\\\":\\\"3\\\",\\\"items\\\":[{\\\"image\\\":null,\\\"title\\\":\\\"Customers\\\",\\\"text\\\":\\\"Name, CNIC, phones, address, photo and every plan they have had.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Flexible plans\\\",\\\"text\\\":\\\"Monthly, every two weeks or weekly. Markup as a percent or a fixed amount. Installments rounded to 10, 50 or 100.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Counter collection\\\",\\\"text\\\":\\\"Search by name, phone, CNIC or plan number. The amount due and the late fee are filled in.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Late fees and discounts\\\",\\\"text\\\":\\\"A late fee per late installment after your grace days, and discounts for early payment.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Cancel safely\\\",\\\"text\\\":\\\"A wrong receipt is cancelled with a reason. Its entry is reversed, never deleted.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Bad debts\\\",\\\"text\\\":\\\"Write off what cannot be recovered. The unearned markup is cancelled automatically.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Expenses and vouchers\\\",\\\"text\\\":\\\"Pay rent, salaries and bills; record capital and loans; make journal entries.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Staff logins\\\",\\\"text\\\":\\\"An owner login and accountant logins, with every change in the audit log.\\\",\\\"link\\\":\\\"\\\"},{\\\"image\\\":null,\\\"title\\\":\\\"Your own website\\\",\\\"text\\\":\\\"A website for your shop, managed from your panel.\\\",\\\"link\\\":\\\"\\\"}]}\",\"sort\":4,\"site_page_id\":4,\"id\":16}}','127.0.0.1','2026-11-04 10:48:45'),(31,NULL,NULL,'System','created','SiteSection',17,NULL,'{\"old\":null,\"new\":{\"type\":\"plans\",\"data\":\"{\\\"_anchor\\\":\\\"pricing\\\",\\\"product\\\":\\\"business\\\",\\\"_eyebrow\\\":\\\"Pricing\\\",\\\"heading\\\":\\\"One simple monthly price\\\",\\\"intro\\\":\\\"Start with a free trial. Choose a plan when you are ready.\\\",\\\"note\\\":\\\"Prices in Pakistani rupees. Ask us about more than one branch.\\\",\\\"button_label\\\":\\\"Start free trial\\\",\\\"button_url\\\":\\\"\\\\\\/signup?product=business\\\",\\\"_bg\\\":\\\"tint\\\"}\",\"sort\":5,\"site_page_id\":4,\"id\":17}}','127.0.0.1','2026-11-04 10:48:45'),(32,NULL,NULL,'System','created','SiteSection',18,NULL,'{\"old\":null,\"new\":{\"type\":\"faq\",\"data\":\"{\\\"_anchor\\\":\\\"faq\\\",\\\"_eyebrow\\\":\\\"Questions\\\",\\\"heading\\\":\\\"Questions businesses ask\\\",\\\"items\\\":[{\\\"question\\\":\\\"What can I sell on installments?\\\",\\\"answer\\\":\\\"Anything: motorcycles, mobiles, LED TVs, fridges, furniture, solar systems, even plots. You type the item and its details.\\\"},{\\\"question\\\":\\\"How is the markup worked out?\\\",\\\"answer\\\":\\\"As a percent of what is left after the down payment, or as a fixed amount you enter. The total, the installment and the schedule are shown before you save.\\\"},{\\\"question\\\":\\\"Do I need to know accounting?\\\",\\\"answer\\\":\\\"No. Sales and payments post to the right accounts by themselves. Your accountant still gets proper ledgers, a trial balance, profit and loss and a balance sheet.\\\"},{\\\"question\\\":\\\"Can I remind customers on WhatsApp?\\\",\\\"answer\\\":\\\"Yes. One tap opens WhatsApp with the reminder written for you, to the customer or a guarantor. You can also queue reminders for everyone who is late.\\\"},{\\\"question\\\":\\\"Can other businesses see my data?\\\",\\\"answer\\\":\\\"No. Every business only ever sees its own customers and accounts.\\\"}]}\",\"sort\":6,\"site_page_id\":4,\"id\":18}}','127.0.0.1','2026-11-04 10:48:45'),(33,NULL,NULL,'System','created','SiteSection',19,NULL,'{\"old\":null,\"new\":{\"type\":\"cta\",\"data\":\"{\\\"_eyebrow\\\":\\\"Free trial\\\",\\\"heading\\\":\\\"Know every rupee your customers owe\\\",\\\"text\\\":\\\"Sign up and your business is ready in a minute. No card, no installation.\\\",\\\"button_label\\\":\\\"Start free trial\\\",\\\"button_url\\\":\\\"\\\\\\/signup?product=business\\\",\\\"_bg\\\":\\\"brand\\\",\\\"_spacing\\\":\\\"large\\\"}\",\"sort\":7,\"site_page_id\":4,\"id\":19}}','127.0.0.1','2026-11-04 10:48:45'),(34,NULL,NULL,'System','created','SiteMenuItem',10,NULL,'{\"old\":null,\"new\":{\"location\":\"header\",\"label\":\"Installments\",\"site_page_id\":4,\"sort\":6,\"site_id\":1,\"id\":10}}','127.0.0.1','2026-11-04 10:48:45'),(35,NULL,NULL,'System','created','SiteMenuItem',11,NULL,'{\"old\":null,\"new\":{\"location\":\"footer\",\"label\":\"Installment software\",\"site_page_id\":4,\"sort\":5,\"site_id\":1,\"id\":11}}','127.0.0.1','2026-11-04 10:48:45');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biometric_devices`
--

DROP TABLE IF EXISTS `biometric_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biometric_devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(10) NOT NULL DEFAULT 'machine',
  `serial_number` varchar(60) NOT NULL,
  `token_hash` varchar(64) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `biometric_devices_serial_number_unique` (`serial_number`),
  UNIQUE KEY `biometric_devices_token_hash_unique` (`token_hash`),
  KEY `biometric_devices_school_id_foreign` (`school_id`),
  CONSTRAINT `biometric_devices_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biometric_devices`
--

LOCK TABLES `biometric_devices` WRITE;
/*!40000 ALTER TABLE `biometric_devices` DISABLE KEYS */;
/*!40000 ALTER TABLE `biometric_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biometric_punches`
--

DROP TABLE IF EXISTS `biometric_punches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biometric_punches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `biometric_device_id` bigint(20) unsigned DEFAULT NULL,
  `staff_id` bigint(20) unsigned DEFAULT NULL,
  `device_user_id` varchar(20) NOT NULL,
  `punched_at` datetime NOT NULL,
  `source` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `biometric_punches_school_id_device_user_id_punched_at_unique` (`school_id`,`device_user_id`,`punched_at`),
  KEY `biometric_punches_biometric_device_id_foreign` (`biometric_device_id`),
  KEY `biometric_punches_staff_id_foreign` (`staff_id`),
  CONSTRAINT `biometric_punches_biometric_device_id_foreign` FOREIGN KEY (`biometric_device_id`) REFERENCES `biometric_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biometric_punches_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `biometric_punches_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biometric_punches`
--

LOCK TABLES `biometric_punches` WRITE;
/*!40000 ALTER TABLE `biometric_punches` DISABLE KEYS */;
/*!40000 ALTER TABLE `biometric_punches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biz_accounts`
--

DROP TABLE IF EXISTS `biz_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biz_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(120) NOT NULL,
  `type` varchar(10) NOT NULL,
  `system_key` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `biz_accounts_school_id_code_unique` (`school_id`,`code`),
  UNIQUE KEY `biz_accounts_school_id_system_key_unique` (`school_id`,`system_key`),
  CONSTRAINT `biz_accounts_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biz_accounts`
--

LOCK TABLES `biz_accounts` WRITE;
/*!40000 ALTER TABLE `biz_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `biz_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biz_brand_models`
--

DROP TABLE IF EXISTS `biz_brand_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biz_brand_models` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `brand_id` bigint(20) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `biz_brand_models_brand_id_name_unique` (`brand_id`,`name`),
  KEY `biz_brand_models_school_id_foreign` (`school_id`),
  CONSTRAINT `biz_brand_models_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `biz_brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `biz_brand_models_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biz_brand_models`
--

LOCK TABLES `biz_brand_models` WRITE;
/*!40000 ALTER TABLE `biz_brand_models` DISABLE KEYS */;
/*!40000 ALTER TABLE `biz_brand_models` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biz_brands`
--

DROP TABLE IF EXISTS `biz_brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biz_brands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `type_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(80) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `biz_brands_school_id_type_id_name_unique` (`school_id`,`type_id`,`name`),
  KEY `biz_brands_type_id_foreign` (`type_id`),
  CONSTRAINT `biz_brands_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `biz_brands_type_id_foreign` FOREIGN KEY (`type_id`) REFERENCES `biz_product_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biz_brands`
--

LOCK TABLES `biz_brands` WRITE;
/*!40000 ALTER TABLE `biz_brands` DISABLE KEYS */;
/*!40000 ALTER TABLE `biz_brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biz_categories`
--

DROP TABLE IF EXISTS `biz_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biz_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(80) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `biz_categories_school_id_name_unique` (`school_id`,`name`),
  CONSTRAINT `biz_categories_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biz_categories`
--

LOCK TABLES `biz_categories` WRITE;
/*!40000 ALTER TABLE `biz_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `biz_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biz_contracts`
--

DROP TABLE IF EXISTS `biz_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biz_contracts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `number` varchar(20) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `stock_unit_id` bigint(20) unsigned DEFAULT NULL,
  `item` varchar(150) NOT NULL,
  `item_details` text DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `sale_date` date NOT NULL,
  `cash_price` bigint(20) unsigned NOT NULL,
  `cost_price` bigint(20) unsigned DEFAULT NULL,
  `markup_percent` decimal(6,2) NOT NULL DEFAULT 0.00,
  `markup` bigint(20) unsigned NOT NULL,
  `total_price` bigint(20) unsigned NOT NULL,
  `down_payment` bigint(20) unsigned NOT NULL DEFAULT 0,
  `financed` bigint(20) unsigned NOT NULL,
  `installments` smallint(5) unsigned NOT NULL,
  `frequency` varchar(12) NOT NULL DEFAULT 'monthly',
  `installment_amount` bigint(20) unsigned NOT NULL,
  `first_due_on` date NOT NULL,
  `late_fee` int(10) unsigned NOT NULL DEFAULT 0,
  `grace_days` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `status` varchar(12) NOT NULL DEFAULT 'active',
  `markup_recognised` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `biz_contracts_school_id_number_unique` (`school_id`,`number`),
  KEY `biz_contracts_customer_id_foreign` (`customer_id`),
  KEY `biz_contracts_created_by_foreign` (`created_by`),
  KEY `biz_contracts_school_id_status_index` (`school_id`,`status`),
  KEY `biz_contracts_product_id_foreign` (`product_id`),
  KEY `biz_contracts_stock_unit_id_foreign` (`stock_unit_id`),
  CONSTRAINT `biz_contracts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_contracts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `biz_customers` (`id`),
  CONSTRAINT `biz_contracts_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `biz_products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_contracts_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `biz_contracts_stock_unit_id_foreign` FOREIGN KEY (`stock_unit_id`) REFERENCES `biz_stock_units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biz_contracts`
--

LOCK TABLES `biz_contracts` WRITE;
/*!40000 ALTER TABLE `biz_contracts` DISABLE KEYS */;
/*!40000 ALTER TABLE `biz_contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biz_customers`
--

DROP TABLE IF EXISTS `biz_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biz_customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(120) NOT NULL,
  `father_name` varchar(120) DEFAULT NULL,
  `cnic` varchar(20) DEFAULT NULL,
  `phone` varchar(30) NOT NULL,
  `phone2` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_blacklisted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `biz_customers_school_id_code_unique` (`school_id`,`code`),
  KEY `biz_customers_school_id_phone_index` (`school_id`,`phone`),
  KEY `biz_customers_school_id_cnic_index` (`school_id`,`cnic`),
  CONSTRAINT `biz_customers_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biz_customers`
--

LOCK TABLES `biz_customers` WRITE;
/*!40000 ALTER TABLE `biz_customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `biz_customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biz_guarantors`
--

DROP TABLE IF EXISTS `biz_guarantors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biz_guarantors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `contract_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `father_name` varchar(120) DEFAULT NULL,
  `cnic` varchar(20) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `relation` varchar(60) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `biz_guarantors_school_id_foreign` (`school_id`),
  KEY `biz_guarantors_contract_id_foreign` (`contract_id`),
  CONSTRAINT `biz_guarantors_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `biz_contracts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `biz_guarantors_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biz_guarantors`
--

LOCK TABLES `biz_guarantors` WRITE;
/*!40000 ALTER TABLE `biz_guarantors` DISABLE KEYS */;
/*!40000 ALTER TABLE `biz_guarantors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biz_invoice_lines`
--

DROP TABLE IF EXISTS `biz_invoice_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biz_invoice_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `unit_price` bigint(20) unsigned NOT NULL,
  `total` bigint(20) unsigned NOT NULL,
  `cost` bigint(20) unsigned NOT NULL DEFAULT 0,
  `details` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `biz_invoice_lines_school_id_foreign` (`school_id`),
  KEY `biz_invoice_lines_invoice_id_foreign` (`invoice_id`),
  KEY `biz_invoice_lines_product_id_foreign` (`product_id`),
  CONSTRAINT `biz_invoice_lines_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `biz_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `biz_invoice_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `biz_products` (`id`),
  CONSTRAINT `biz_invoice_lines_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biz_invoice_lines`
--

LOCK TABLES `biz_invoice_lines` WRITE;
/*!40000 ALTER TABLE `biz_invoice_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `biz_invoice_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biz_invoice_payments`
--

DROP TABLE IF EXISTS `biz_invoice_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biz_invoice_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `amount` bigint(20) unsigned NOT NULL,
  `method` varchar(12) NOT NULL DEFAULT 'cash',
  `reference` varchar(60) DEFAULT NULL,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `biz_invoice_payments_invoice_id_foreign` (`invoice_id`),
  KEY `biz_invoice_payments_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `biz_invoice_payments_received_by_foreign` (`received_by`),
  KEY `biz_invoice_payments_school_id_date_index` (`school_id`,`date`),
  CONSTRAINT `biz_invoice_payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `biz_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `biz_invoice_payments_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `biz_journal_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_invoice_payments_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_invoice_payments_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biz_invoice_payments`
--

LOCK TABLES `biz_invoice_payments` WRITE;
/*!40000 ALTER TABLE `biz_invoice_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `biz_invoice_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biz_invoices`
--

DROP TABLE IF EXISTS `biz_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biz_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `number` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `customer_name` varchar(120) DEFAULT NULL,
  `subtotal` bigint(20) unsigned NOT NULL,
  `discount` bigint(20) unsigned NOT NULL DEFAULT 0,
  `total` bigint(20) unsigned NOT NULL,
  `cost` bigint(20) unsigned NOT NULL DEFAULT 0,
  `paid` bigint(20) unsigned NOT NULL DEFAULT 0,
  `notes` varchar(255) DEFAULT NULL,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `voided_at` timestamp NULL DEFAULT NULL,
  `void_reason` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `biz_invoices_school_id_number_unique` (`school_id`,`number`),
  KEY `biz_invoices_customer_id_foreign` (`customer_id`),
  KEY `biz_invoices_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `biz_invoices_created_by_foreign` (`created_by`),
  KEY `biz_invoices_school_id_date_index` (`school_id`,`date`),
  CONSTRAINT `biz_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `biz_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_invoices_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `biz_journal_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_invoices_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biz_invoices`
--

LOCK TABLES `biz_invoices` WRITE;
/*!40000 ALTER TABLE `biz_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `biz_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biz_journal_entries`
--

DROP TABLE IF EXISTS `biz_journal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biz_journal_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `number` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `kind` varchar(12) NOT NULL,
  `memo` varchar(255) DEFAULT NULL,
  `source_type` varchar(255) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `reversal_of` bigint(20) unsigned DEFAULT NULL,
  `reversed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `biz_journal_entries_school_id_number_unique` (`school_id`,`number`),
  KEY `biz_journal_entries_source_type_source_id_index` (`source_type`,`source_id`),
  KEY `biz_journal_entries_reversal_of_foreign` (`reversal_of`),
  KEY `biz_journal_entries_created_by_foreign` (`created_by`),
  KEY `biz_journal_entries_school_id_date_index` (`school_id`,`date`),
  CONSTRAINT `biz_journal_entries_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_journal_entries_reversal_of_foreign` FOREIGN KEY (`reversal_of`) REFERENCES `biz_journal_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_journal_entries_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biz_journal_entries`
--

LOCK TABLES `biz_journal_entries` WRITE;
/*!40000 ALTER TABLE `biz_journal_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `biz_journal_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biz_journal_lines`
--

DROP TABLE IF EXISTS `biz_journal_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biz_journal_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `entry_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `debit` bigint(20) unsigned NOT NULL DEFAULT 0,
  `credit` bigint(20) unsigned NOT NULL DEFAULT 0,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `memo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `biz_journal_lines_entry_id_foreign` (`entry_id`),
  KEY `biz_journal_lines_account_id_foreign` (`account_id`),
  KEY `biz_journal_lines_customer_id_foreign` (`customer_id`),
  KEY `biz_journal_lines_school_id_account_id_index` (`school_id`,`account_id`),
  CONSTRAINT `biz_journal_lines_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `biz_accounts` (`id`),
  CONSTRAINT `biz_journal_lines_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `biz_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_journal_lines_entry_id_foreign` FOREIGN KEY (`entry_id`) REFERENCES `biz_journal_entries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `biz_journal_lines_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biz_journal_lines`
--

LOCK TABLES `biz_journal_lines` WRITE;
/*!40000 ALTER TABLE `biz_journal_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `biz_journal_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biz_product_types`
--

DROP TABLE IF EXISTS `biz_product_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biz_product_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(80) NOT NULL,
  `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`fields`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `biz_product_types_school_id_name_unique` (`school_id`,`name`),
  CONSTRAINT `biz_product_types_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biz_product_types`
--

LOCK TABLES `biz_product_types` WRITE;
/*!40000 ALTER TABLE `biz_product_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `biz_product_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biz_products`
--

DROP TABLE IF EXISTS `biz_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biz_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `type_id` bigint(20) unsigned DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `model_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(30) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `variant` varchar(80) DEFAULT NULL,
  `brand` varchar(80) DEFAULT NULL,
  `sale_price` bigint(20) unsigned NOT NULL DEFAULT 0,
  `wholesale_price` bigint(20) unsigned NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `biz_products_school_id_code_unique` (`school_id`,`code`),
  KEY `biz_products_type_id_foreign` (`type_id`),
  KEY `biz_products_school_id_name_index` (`school_id`,`name`),
  KEY `biz_products_category_id_foreign` (`category_id`),
  KEY `biz_products_brand_id_foreign` (`brand_id`),
  KEY `biz_products_model_id_foreign` (`model_id`),
  CONSTRAINT `biz_products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `biz_brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `biz_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_products_model_id_foreign` FOREIGN KEY (`model_id`) REFERENCES `biz_brand_models` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_products_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `biz_products_type_id_foreign` FOREIGN KEY (`type_id`) REFERENCES `biz_product_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biz_products`
--

LOCK TABLES `biz_products` WRITE;
/*!40000 ALTER TABLE `biz_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `biz_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biz_purchases`
--

DROP TABLE IF EXISTS `biz_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biz_purchases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `number` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `supplier` varchar(120) DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `unit_cost` bigint(20) unsigned NOT NULL,
  `paid_by` varchar(12) NOT NULL DEFAULT 'cash',
  `reference` varchar(60) DEFAULT NULL,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `biz_purchases_school_id_number_unique` (`school_id`,`number`),
  KEY `biz_purchases_product_id_foreign` (`product_id`),
  KEY `biz_purchases_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `biz_purchases_created_by_foreign` (`created_by`),
  CONSTRAINT `biz_purchases_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_purchases_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `biz_journal_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_purchases_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `biz_products` (`id`),
  CONSTRAINT `biz_purchases_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biz_purchases`
--

LOCK TABLES `biz_purchases` WRITE;
/*!40000 ALTER TABLE `biz_purchases` DISABLE KEYS */;
/*!40000 ALTER TABLE `biz_purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biz_receipts`
--

DROP TABLE IF EXISTS `biz_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biz_receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `number` varchar(20) NOT NULL,
  `contract_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `is_down_payment` tinyint(1) NOT NULL DEFAULT 0,
  `amount` bigint(20) unsigned NOT NULL,
  `late_fee` int(10) unsigned NOT NULL DEFAULT 0,
  `discount` int(10) unsigned NOT NULL DEFAULT 0,
  `method` varchar(12) NOT NULL DEFAULT 'cash',
  `reference` varchar(60) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `voided_at` timestamp NULL DEFAULT NULL,
  `void_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `biz_receipts_school_id_number_unique` (`school_id`,`number`),
  KEY `biz_receipts_contract_id_foreign` (`contract_id`),
  KEY `biz_receipts_received_by_foreign` (`received_by`),
  KEY `biz_receipts_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `biz_receipts_school_id_date_index` (`school_id`,`date`),
  CONSTRAINT `biz_receipts_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `biz_contracts` (`id`),
  CONSTRAINT `biz_receipts_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `biz_journal_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_receipts_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_receipts_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biz_receipts`
--

LOCK TABLES `biz_receipts` WRITE;
/*!40000 ALTER TABLE `biz_receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `biz_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biz_schedule`
--

DROP TABLE IF EXISTS `biz_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biz_schedule` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `contract_id` bigint(20) unsigned NOT NULL,
  `number` smallint(5) unsigned NOT NULL,
  `due_on` date NOT NULL,
  `amount` bigint(20) unsigned NOT NULL,
  `paid` bigint(20) unsigned NOT NULL DEFAULT 0,
  `paid_on` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `biz_schedule_school_id_due_on_index` (`school_id`,`due_on`),
  KEY `biz_schedule_contract_id_index` (`contract_id`),
  CONSTRAINT `biz_schedule_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `biz_contracts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `biz_schedule_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biz_schedule`
--

LOCK TABLES `biz_schedule` WRITE;
/*!40000 ALTER TABLE `biz_schedule` DISABLE KEYS */;
/*!40000 ALTER TABLE `biz_schedule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biz_stock_units`
--

DROP TABLE IF EXISTS `biz_stock_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biz_stock_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `unit_cost` bigint(20) unsigned NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `status` varchar(10) NOT NULL DEFAULT 'in_stock',
  `contract_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_line_id` bigint(20) unsigned DEFAULT NULL,
  `sold_on` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `biz_stock_units_product_id_foreign` (`product_id`),
  KEY `biz_stock_units_purchase_id_foreign` (`purchase_id`),
  KEY `biz_stock_units_contract_id_foreign` (`contract_id`),
  KEY `biz_stock_units_school_id_product_id_status_index` (`school_id`,`product_id`,`status`),
  KEY `biz_stock_units_invoice_line_id_foreign` (`invoice_line_id`),
  CONSTRAINT `biz_stock_units_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `biz_contracts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_stock_units_invoice_line_id_foreign` FOREIGN KEY (`invoice_line_id`) REFERENCES `biz_invoice_lines` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biz_stock_units_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `biz_products` (`id`),
  CONSTRAINT `biz_stock_units_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `biz_purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `biz_stock_units_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biz_stock_units`
--

LOCK TABLES `biz_stock_units` WRITE;
/*!40000 ALTER TABLE `biz_stock_units` DISABLE KEYS */;
/*!40000 ALTER TABLE `biz_stock_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_loans`
--

DROP TABLE IF EXISTS `book_loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `book_loans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `book_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `issued_on` date NOT NULL,
  `due_on` date NOT NULL,
  `returned_on` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `book_loans_school_id_foreign` (`school_id`),
  KEY `book_loans_book_id_foreign` (`book_id`),
  KEY `book_loans_student_id_foreign` (`student_id`),
  CONSTRAINT `book_loans_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `book_loans_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `book_loans_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_loans`
--

LOCK TABLES `book_loans` WRITE;
/*!40000 ALTER TABLE `book_loans` DISABLE KEYS */;
/*!40000 ALTER TABLE `book_loans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `books` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(255) DEFAULT NULL,
  `copies` smallint(5) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `books_school_id_foreign` (`school_id`),
  CONSTRAINT `books_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `books`
--

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_closings`
--

DROP TABLE IF EXISTS `cash_closings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_closings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `expected` int(10) unsigned NOT NULL,
  `counted` int(10) unsigned NOT NULL,
  `difference` int(11) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cash_closings_school_id_date_unique` (`school_id`,`date`),
  KEY `cash_closings_closed_by_foreign` (`closed_by`),
  CONSTRAINT `cash_closings_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cash_closings_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_closings`
--

LOCK TABLES `cash_closings` WRITE;
/*!40000 ALTER TABLE `cash_closings` DISABLE KEYS */;
/*!40000 ALTER TABLE `cash_closings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certificates`
--

DROP TABLE IF EXISTS `certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `serial` varchar(255) NOT NULL,
  `token` varchar(40) NOT NULL,
  `issued_on` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `issued_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificates_school_id_serial_unique` (`school_id`,`serial`),
  UNIQUE KEY `certificates_token_unique` (`token`),
  KEY `certificates_student_id_foreign` (`student_id`),
  KEY `certificates_issued_by_foreign` (`issued_by`),
  CONSTRAINT `certificates_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `certificates_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `certificates_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificates`
--

LOCK TABLES `certificates` WRITE;
/*!40000 ALTER TABLE `certificates` DISABLE KEYS */;
/*!40000 ALTER TABLE `certificates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `class_subjects`
--

DROP TABLE IF EXISTS `class_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `class_subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `school_class_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `staff_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_subjects_school_class_id_subject_id_unique` (`school_class_id`,`subject_id`),
  KEY `class_subjects_school_id_foreign` (`school_id`),
  KEY `class_subjects_subject_id_foreign` (`subject_id`),
  KEY `class_subjects_staff_id_foreign` (`staff_id`),
  CONSTRAINT `class_subjects_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_subjects_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_subjects_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `class_subjects_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_subjects`
--

LOCK TABLES `class_subjects` WRITE;
/*!40000 ALTER TABLE `class_subjects` DISABLE KEYS */;
/*!40000 ALTER TABLE `class_subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `diaries`
--

DROP TABLE IF EXISTS `diaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `diaries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `section_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `diaries_school_id_foreign` (`school_id`),
  KEY `diaries_subject_id_foreign` (`subject_id`),
  KEY `diaries_created_by_foreign` (`created_by`),
  KEY `diaries_section_id_date_index` (`section_id`,`date`),
  CONSTRAINT `diaries_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `diaries_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `diaries_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `diaries_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `diaries`
--

LOCK TABLES `diaries` WRITE;
/*!40000 ALTER TABLE `diaries` DISABLE KEYS */;
/*!40000 ALTER TABLE `diaries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_papers`
--

DROP TABLE IF EXISTS `exam_papers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_papers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `exam_id` bigint(20) unsigned NOT NULL,
  `school_class_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `total_marks` smallint(5) unsigned NOT NULL DEFAULT 100,
  `pass_marks` smallint(5) unsigned NOT NULL DEFAULT 33,
  `paper_on` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_papers_exam_id_school_class_id_subject_id_unique` (`exam_id`,`school_class_id`,`subject_id`),
  KEY `exam_papers_school_id_foreign` (`school_id`),
  KEY `exam_papers_school_class_id_foreign` (`school_class_id`),
  KEY `exam_papers_subject_id_foreign` (`subject_id`),
  CONSTRAINT `exam_papers_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_papers_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_papers_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_papers_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_papers`
--

LOCK TABLES `exam_papers` WRITE;
/*!40000 ALTER TABLE `exam_papers` DISABLE KEYS */;
/*!40000 ALTER TABLE `exam_papers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exams` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `academic_session_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `starts_on` date DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exams_school_id_foreign` (`school_id`),
  KEY `exams_academic_session_id_foreign` (`academic_session_id`),
  CONSTRAINT `exams_academic_session_id_foreign` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exams_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exams`
--

LOCK TABLES `exams` WRITE;
/*!40000 ALTER TABLE `exams` DISABLE KEYS */;
/*!40000 ALTER TABLE `exams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `category` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` int(10) unsigned NOT NULL,
  `spent_on` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_school_id_spent_on_index` (`school_id`,`spent_on`),
  CONSTRAINT `expenses_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_heads`
--

DROP TABLE IF EXISTS `fee_heads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fee_heads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_monthly` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fee_heads_school_id_name_unique` (`school_id`,`name`),
  CONSTRAINT `fee_heads_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_heads`
--

LOCK TABLES `fee_heads` WRITE;
/*!40000 ALTER TABLE `fee_heads` DISABLE KEYS */;
/*!40000 ALTER TABLE `fee_heads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_structures`
--

DROP TABLE IF EXISTS `fee_structures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fee_structures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `school_class_id` bigint(20) unsigned NOT NULL,
  `fee_head_id` bigint(20) unsigned NOT NULL,
  `amount` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fee_structures_school_class_id_fee_head_id_unique` (`school_class_id`,`fee_head_id`),
  KEY `fee_structures_school_id_foreign` (`school_id`),
  KEY `fee_structures_fee_head_id_foreign` (`fee_head_id`),
  CONSTRAINT `fee_structures_fee_head_id_foreign` FOREIGN KEY (`fee_head_id`) REFERENCES `fee_heads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_structures_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_structures_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_structures`
--

LOCK TABLES `fee_structures` WRITE;
/*!40000 ALTER TABLE `fee_structures` DISABLE KEYS */;
/*!40000 ALTER TABLE `fee_structures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_voucher_lines`
--

DROP TABLE IF EXISTS `fee_voucher_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fee_voucher_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fee_voucher_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `label` varchar(255) NOT NULL,
  `amount` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fee_voucher_lines_fee_voucher_id_foreign` (`fee_voucher_id`),
  KEY `fee_voucher_lines_student_id_foreign` (`student_id`),
  CONSTRAINT `fee_voucher_lines_fee_voucher_id_foreign` FOREIGN KEY (`fee_voucher_id`) REFERENCES `fee_vouchers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_voucher_lines_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_voucher_lines`
--

LOCK TABLES `fee_voucher_lines` WRITE;
/*!40000 ALTER TABLE `fee_voucher_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `fee_voucher_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_vouchers`
--

DROP TABLE IF EXISTS `fee_vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fee_vouchers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `guardian_id` bigint(20) unsigned NOT NULL,
  `voucher_no` varchar(255) NOT NULL,
  `month` date NOT NULL,
  `due_on` date NOT NULL,
  `subtotal` int(11) NOT NULL DEFAULT 0,
  `late_fee` int(10) unsigned NOT NULL DEFAULT 0,
  `late_fee_waived` tinyint(1) NOT NULL DEFAULT 0,
  `paid` int(10) unsigned NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'unpaid',
  `void_reason` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fee_vouchers_school_id_voucher_no_unique` (`school_id`,`voucher_no`),
  KEY `fee_vouchers_created_by_foreign` (`created_by`),
  KEY `fee_vouchers_school_id_month_status_index` (`school_id`,`month`,`status`),
  KEY `fee_vouchers_guardian_id_month_index` (`guardian_id`,`month`),
  CONSTRAINT `fee_vouchers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fee_vouchers_guardian_id_foreign` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`),
  CONSTRAINT `fee_vouchers_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_vouchers`
--

LOCK TABLES `fee_vouchers` WRITE;
/*!40000 ALTER TABLE `fee_vouchers` DISABLE KEYS */;
/*!40000 ALTER TABLE `fee_vouchers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fingerprint_templates`
--

DROP TABLE IF EXISTS `fingerprint_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fingerprint_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `finger` varchar(20) NOT NULL,
  `template` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fingerprint_templates_school_id_foreign` (`school_id`),
  KEY `fingerprint_templates_staff_id_foreign` (`staff_id`),
  CONSTRAINT `fingerprint_templates_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fingerprint_templates_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fingerprint_templates`
--

LOCK TABLES `fingerprint_templates` WRITE;
/*!40000 ALTER TABLE `fingerprint_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `fingerprint_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guardians`
--

DROP TABLE IF EXISTS `guardians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guardians` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `relation` varchar(30) DEFAULT NULL,
  `phone` varchar(255) NOT NULL,
  `cnic` varchar(255) DEFAULT NULL,
  `occupation` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `guardians_school_id_phone_unique` (`school_id`,`phone`),
  KEY `guardians_user_id_foreign` (`user_id`),
  CONSTRAINT `guardians_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `guardians_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guardians`
--

LOCK TABLES `guardians` WRITE;
/*!40000 ALTER TABLE `guardians` DISABLE KEYS */;
/*!40000 ALTER TABLE `guardians` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inquiries`
--

DROP TABLE IF EXISTS `inquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inquiries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `school_class_id` bigint(20) unsigned DEFAULT NULL,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `student_name` varchar(255) NOT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `phone` varchar(255) NOT NULL,
  `source` varchar(255) NOT NULL DEFAULT 'walk-in',
  `stage` varchar(255) NOT NULL DEFAULT 'new',
  `follow_up_on` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inquiries_school_class_id_foreign` (`school_class_id`),
  KEY `inquiries_student_id_foreign` (`student_id`),
  KEY `inquiries_school_id_stage_index` (`school_id`,`stage`),
  CONSTRAINT `inquiries_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inquiries_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inquiries_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inquiries`
--

LOCK TABLES `inquiries` WRITE;
/*!40000 ALTER TABLE `inquiries` DISABLE KEYS */;
/*!40000 ALTER TABLE `inquiries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marks`
--

DROP TABLE IF EXISTS `marks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `marks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `exam_paper_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `obtained` decimal(6,2) DEFAULT NULL,
  `entered_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `marks_exam_paper_id_student_id_unique` (`exam_paper_id`,`student_id`),
  KEY `marks_school_id_foreign` (`school_id`),
  KEY `marks_student_id_foreign` (`student_id`),
  KEY `marks_entered_by_foreign` (`entered_by`),
  CONSTRAINT `marks_entered_by_foreign` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `marks_exam_paper_id_foreign` FOREIGN KEY (`exam_paper_id`) REFERENCES `exam_papers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `marks_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `marks_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marks`
--

LOCK TABLES `marks` WRITE;
/*!40000 ALTER TABLE `marks` DISABLE KEYS */;
/*!40000 ALTER TABLE `marks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `guardian_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `phone` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'general',
  `body` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'queued',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_guardian_id_foreign` (`guardian_id`),
  KEY `messages_created_by_foreign` (`created_by`),
  KEY `messages_school_id_status_index` (`school_id`,`status`),
  CONSTRAINT `messages_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `messages_guardian_id_foreign` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`) ON DELETE SET NULL,
  CONSTRAINT `messages_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_11_01_000001_create_core_tables',1),(5,'2026_11_01_000002_create_finance_and_exam_tables',1),(6,'2026_11_02_000001_create_timetables_table',1),(7,'2026_11_03_000001_create_site_tables',1),(8,'2026_11_03_000002_add_parent_to_site_menu_items',1),(9,'2026_11_04_000001_add_domains_to_sites',1),(10,'2026_11_04_000001_add_subscriptions',1),(11,'2026_11_05_000001_add_id_cards',1),(12,'2026_11_05_000001_create_plans_table',1),(13,'2026_11_05_000002_create_pickup_people',1),(14,'2026_11_06_000001_create_business_tables',1),(15,'2026_11_06_000001_upgrade_staff_attendance',1),(16,'2026_11_06_000002_add_product_to_plans',1),(17,'2026_11_07_000001_biometric_and_strict_attendance',1),(18,'2026_11_07_000001_create_business_products',1),(19,'2026_11_07_000002_seed_product_types_for_businesses',1),(20,'2026_11_08_000001_fingerprint_kiosk',1),(21,'2026_11_09_000001_create_brands_categories_invoices',1),(22,'2026_11_09_000002_seed_catalog_for_businesses',1),(23,'2026_11_09_000003_add_sales_to_business_plans',1),(24,'2026_11_10_000001_add_business_kind_to_schools',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_proofs`
--

DROP TABLE IF EXISTS `payment_proofs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_proofs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `fee_voucher_id` bigint(20) unsigned NOT NULL,
  `guardian_id` bigint(20) unsigned NOT NULL,
  `amount` int(10) unsigned NOT NULL,
  `method` varchar(255) NOT NULL,
  `reference` varchar(255) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `review_note` varchar(255) DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_proofs_fee_voucher_id_foreign` (`fee_voucher_id`),
  KEY `payment_proofs_guardian_id_foreign` (`guardian_id`),
  KEY `payment_proofs_reviewed_by_foreign` (`reviewed_by`),
  KEY `payment_proofs_payment_id_foreign` (`payment_id`),
  KEY `payment_proofs_school_id_status_index` (`school_id`,`status`),
  KEY `payment_proofs_school_id_method_reference_index` (`school_id`,`method`,`reference`),
  CONSTRAINT `payment_proofs_fee_voucher_id_foreign` FOREIGN KEY (`fee_voucher_id`) REFERENCES `fee_vouchers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_proofs_guardian_id_foreign` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_proofs_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_proofs_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_proofs_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_proofs`
--

LOCK TABLES `payment_proofs` WRITE;
/*!40000 ALTER TABLE `payment_proofs` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_proofs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `fee_voucher_id` bigint(20) unsigned NOT NULL,
  `receipt_no` varchar(255) NOT NULL,
  `amount` int(10) unsigned NOT NULL,
  `method` varchar(255) NOT NULL DEFAULT 'cash',
  `reference` varchar(255) DEFAULT NULL,
  `paid_on` date NOT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `voided_at` timestamp NULL DEFAULT NULL,
  `voided_by` bigint(20) unsigned DEFAULT NULL,
  `void_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_school_id_receipt_no_unique` (`school_id`,`receipt_no`),
  KEY `payments_fee_voucher_id_foreign` (`fee_voucher_id`),
  KEY `payments_received_by_foreign` (`received_by`),
  KEY `payments_voided_by_foreign` (`voided_by`),
  KEY `payments_school_id_paid_on_index` (`school_id`,`paid_on`),
  CONSTRAINT `payments_fee_voucher_id_foreign` FOREIGN KEY (`fee_voucher_id`) REFERENCES `fee_vouchers` (`id`),
  CONSTRAINT `payments_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_voided_by_foreign` FOREIGN KEY (`voided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payrolls`
--

DROP TABLE IF EXISTS `payrolls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payrolls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `month` date NOT NULL,
  `basic` int(10) unsigned NOT NULL,
  `allowance` int(10) unsigned NOT NULL DEFAULT 0,
  `absent_days` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `half_days` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `late_days` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `deducted_days` decimal(5,1) NOT NULL DEFAULT 0.0,
  `deduction` int(10) unsigned NOT NULL DEFAULT 0,
  `net` int(10) unsigned NOT NULL,
  `paid_on` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payrolls_staff_id_month_unique` (`staff_id`,`month`),
  KEY `payrolls_school_id_foreign` (`school_id`),
  CONSTRAINT `payrolls_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payrolls_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payrolls`
--

LOCK TABLES `payrolls` WRITE;
/*!40000 ALTER TABLE `payrolls` DISABLE KEYS */;
/*!40000 ALTER TABLE `payrolls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pickup_people`
--

DROP TABLE IF EXISTS `pickup_people`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pickup_people` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `guardian_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `relation` varchar(30) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `cnic` varchar(20) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pickup_people_guardian_id_foreign` (`guardian_id`),
  KEY `pickup_people_school_id_guardian_id_index` (`school_id`,`guardian_id`),
  CONSTRAINT `pickup_people_guardian_id_foreign` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pickup_people_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pickup_people`
--

LOCK TABLES `pickup_people` WRITE;
/*!40000 ALTER TABLE `pickup_people` DISABLE KEYS */;
/*!40000 ALTER TABLE `pickup_people` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plans`
--

DROP TABLE IF EXISTS `plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) NOT NULL,
  `product` varchar(12) NOT NULL DEFAULT 'school',
  `name` varchar(255) NOT NULL,
  `monthly_price` int(10) unsigned NOT NULL DEFAULT 0,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`features`)),
  `description` varchar(255) DEFAULT NULL,
  `sort` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plans_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plans`
--

LOCK TABLES `plans` WRITE;
/*!40000 ALTER TABLE `plans` DISABLE KEYS */;
INSERT INTO `plans` VALUES (1,'basic','school','Basic',0,'[\"admissions\",\"attendance\",\"diary\",\"timetable\",\"exams\",\"fees\",\"certificates\",\"messages\",\"portal\",\"staff\"]','Everyday school office',1,'2026-11-04 10:48:39','2026-11-04 10:48:39'),(2,'pro','school','Pro',0,'[\"admissions\",\"attendance\",\"diary\",\"timetable\",\"exams\",\"fees\",\"finance\",\"staff\",\"payroll\",\"biometric\",\"certificates\",\"library\",\"transport\",\"messages\",\"risk\",\"portal\",\"website\",\"installments\",\"accounts\",\"sales\"]','Every feature, including the website',2,'2026-11-04 10:48:39','2026-11-04 10:48:39'),(3,'business','business','Business',0,'[\"installments\",\"accounts\",\"messages\",\"website\",\"sales\"]','Installment sales, recovery and full accounts',3,'2026-11-04 10:48:41','2026-11-04 10:48:41');
/*!40000 ALTER TABLE `plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `school_classes`
--

DROP TABLE IF EXISTS `school_classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_classes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `stream` varchar(255) DEFAULT NULL,
  `sort` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `school_classes_school_id_name_stream_unique` (`school_id`,`name`,`stream`),
  CONSTRAINT `school_classes_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `school_classes`
--

LOCK TABLES `school_classes` WRITE;
/*!40000 ALTER TABLE `school_classes` DISABLE KEYS */;
/*!40000 ALTER TABLE `school_classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schools`
--

DROP TABLE IF EXISTS `schools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schools` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'school',
  `business_kind` varchar(20) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `principal_name` varchar(255) DEFAULT NULL,
  `plan` varchar(255) NOT NULL DEFAULT 'trial',
  `trial_ends_at` date DEFAULT NULL,
  `monthly_fee` int(10) unsigned NOT NULL DEFAULT 0,
  `access_until` date DEFAULT NULL,
  `grace_days` tinyint(3) unsigned NOT NULL DEFAULT 7,
  `lock_when_due` tinyint(1) NOT NULL DEFAULT 1,
  `features_off` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features_off`)),
  `owner_note` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `fee_due_day` tinyint(3) unsigned NOT NULL DEFAULT 10,
  `late_fee_per_day` int(10) unsigned NOT NULL DEFAULT 0,
  `late_fee_max` int(10) unsigned NOT NULL DEFAULT 0,
  `sibling_discount_percent` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `bank_details` text DEFAULT NULL,
  `jazzcash_number` varchar(255) DEFAULT NULL,
  `easypaisa_number` varchar(255) DEFAULT NULL,
  `absent_alerts` tinyint(1) NOT NULL DEFAULT 1,
  `risk_attendance_threshold` tinyint(3) unsigned NOT NULL DEFAULT 75,
  `staff_starts_at` varchar(5) NOT NULL DEFAULT '08:00',
  `staff_ends_at` varchar(5) NOT NULL DEFAULT '14:00',
  `staff_late_minutes` tinyint(3) unsigned NOT NULL DEFAULT 10,
  `lates_per_absent` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `staff_self_check_in` tinyint(1) NOT NULL DEFAULT 0,
  `strict_attendance` tinyint(1) NOT NULL DEFAULT 0,
  `check_in_networks` text DEFAULT NULL,
  `check_in_lat` decimal(10,7) DEFAULT NULL,
  `check_in_lng` decimal(10,7) DEFAULT NULL,
  `check_in_radius` smallint(5) unsigned NOT NULL DEFAULT 150,
  `card_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`card_settings`)),
  `business_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`business_settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `schools_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schools`
--

LOCK TABLES `schools` WRITE;
/*!40000 ALTER TABLE `schools` DISABLE KEYS */;
/*!40000 ALTER TABLE `schools` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sections`
--

DROP TABLE IF EXISTS `sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `school_class_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `class_teacher_id` bigint(20) unsigned DEFAULT NULL,
  `capacity` smallint(5) unsigned NOT NULL DEFAULT 40,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sections_school_class_id_name_unique` (`school_class_id`,`name`),
  KEY `sections_school_id_foreign` (`school_id`),
  KEY `sections_class_teacher_id_foreign` (`class_teacher_id`),
  CONSTRAINT `sections_class_teacher_id_foreign` FOREIGN KEY (`class_teacher_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sections_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sections_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sections`
--

LOCK TABLES `sections` WRITE;
/*!40000 ALTER TABLE `sections` DISABLE KEYS */;
/*!40000 ALTER TABLE `sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_media`
--

DROP TABLE IF EXISTS `site_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint(20) unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site_media_site_id_foreign` (`site_id`),
  CONSTRAINT `site_media_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_media`
--

LOCK TABLES `site_media` WRITE;
/*!40000 ALTER TABLE `site_media` DISABLE KEYS */;
/*!40000 ALTER TABLE `site_media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_menu_items`
--

DROP TABLE IF EXISTS `site_menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_menu_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `location` varchar(10) NOT NULL,
  `label` varchar(255) NOT NULL,
  `site_page_id` bigint(20) unsigned DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `new_tab` tinyint(1) NOT NULL DEFAULT 0,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `sort` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site_menu_items_site_id_foreign` (`site_id`),
  KEY `site_menu_items_site_page_id_foreign` (`site_page_id`),
  KEY `site_menu_items_parent_id_foreign` (`parent_id`),
  CONSTRAINT `site_menu_items_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `site_menu_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `site_menu_items_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `site_menu_items_site_page_id_foreign` FOREIGN KEY (`site_page_id`) REFERENCES `site_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_menu_items`
--

LOCK TABLES `site_menu_items` WRITE;
/*!40000 ALTER TABLE `site_menu_items` DISABLE KEYS */;
INSERT INTO `site_menu_items` VALUES (1,1,NULL,'header','Features',NULL,'/#features',0,1,1,'2026-11-04 10:48:44','2026-11-04 10:48:44'),(2,1,NULL,'header','How it works',NULL,'/#how',0,1,2,'2026-11-04 10:48:44','2026-11-04 10:48:44'),(3,1,NULL,'header','Pricing',NULL,'/#pricing',0,1,3,'2026-11-04 10:48:44','2026-11-04 10:48:44'),(4,1,NULL,'header','Questions',NULL,'/#faq',0,1,4,'2026-11-04 10:48:45','2026-11-04 10:48:45'),(5,1,NULL,'header','Contact',3,NULL,0,1,5,'2026-11-04 10:48:45','2026-11-04 10:48:45'),(6,1,NULL,'footer','All features',2,NULL,0,1,1,'2026-11-04 10:48:45','2026-11-04 10:48:45'),(7,1,NULL,'footer','Pricing',NULL,'/#pricing',0,1,2,'2026-11-04 10:48:45','2026-11-04 10:48:45'),(8,1,NULL,'footer','Contact',3,NULL,0,1,3,'2026-11-04 10:48:45','2026-11-04 10:48:45'),(9,1,NULL,'footer','Sign in',NULL,'/login',0,1,4,'2026-11-04 10:48:45','2026-11-04 10:48:45'),(10,1,NULL,'header','Installments',4,NULL,0,1,6,'2026-11-04 10:48:45','2026-11-04 10:48:45'),(11,1,NULL,'footer','Installment software',4,NULL,0,1,5,'2026-11-04 10:48:45','2026-11-04 10:48:45');
/*!40000 ALTER TABLE `site_menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_messages`
--

DROP TABLE IF EXISTS `site_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site_messages_site_id_foreign` (`site_id`),
  CONSTRAINT `site_messages_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_messages`
--

LOCK TABLES `site_messages` WRITE;
/*!40000 ALTER TABLE `site_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `site_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_pages`
--

DROP TABLE IF EXISTS `site_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `is_home` tinyint(1) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `seo_description` text DEFAULT NULL,
  `sort` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_pages_site_id_slug_unique` (`site_id`,`slug`),
  CONSTRAINT `site_pages_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_pages`
--

LOCK TABLES `site_pages` WRITE;
/*!40000 ALTER TABLE `site_pages` DISABLE KEYS */;
INSERT INTO `site_pages` VALUES (1,1,'Home','home',1,1,NULL,1,'2026-11-04 10:48:44','2026-11-04 10:48:44'),(2,1,'All features','features',0,1,NULL,2,'2026-11-04 10:48:44','2026-11-04 10:48:44'),(3,1,'Contact','contact',0,1,NULL,3,'2026-11-04 10:48:44','2026-11-04 10:48:44'),(4,1,'Installments & accounts','installments',0,1,NULL,4,'2026-11-04 10:48:45','2026-11-04 10:48:45');
/*!40000 ALTER TABLE `site_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_permissions`
--

DROP TABLE IF EXISTS `site_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `permission` varchar(40) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_permissions_site_id_user_id_permission_unique` (`site_id`,`user_id`,`permission`),
  KEY `site_permissions_user_id_foreign` (`user_id`),
  CONSTRAINT `site_permissions_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `site_permissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_permissions`
--

LOCK TABLES `site_permissions` WRITE;
/*!40000 ALTER TABLE `site_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `site_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_sections`
--

DROP TABLE IF EXISTS `site_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_page_id` bigint(20) unsigned NOT NULL,
  `type` varchar(30) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `sort` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site_sections_site_page_id_foreign` (`site_page_id`),
  CONSTRAINT `site_sections_site_page_id_foreign` FOREIGN KEY (`site_page_id`) REFERENCES `site_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_sections`
--

LOCK TABLES `site_sections` WRITE;
/*!40000 ALTER TABLE `site_sections` DISABLE KEYS */;
INSERT INTO `site_sections` VALUES (1,1,'hero','{\"_anchor\":\"top\",\"_eyebrow\":\"Made for schools and colleges in Pakistan\",\"layout\":\"product\",\"heading\":\"Run your whole school from one simple system\",\"highlight\":\"one simple system\",\"text\":\"Fees, attendance, results, timetable and parents in one place. MSK Devs does the paperwork, so your staff can spend their time on students.\",\"button_label\":\"Start free trial\",\"button_url\":\"\\/signup\",\"button2_label\":\"See how it works\",\"button2_url\":\"\\/#how\",\"chips\":\"30-day free trial\\nEnglish and Urdu\\nWorks on any phone\\nEach school\'s data kept apart\",\"_bg\":\"dark\",\"_spacing\":\"large\"}',1,1,'2026-11-04 10:48:44','2026-11-04 10:48:44'),(2,1,'features','{\"_anchor\":\"features\",\"_eyebrow\":\"Everything in one place\",\"heading\":\"One system instead of five registers\",\"intro\":\"Everything a school office does every day, in screens your staff learn in an afternoon.\",\"style\":\"bento\",\"columns\":\"3\",\"items\":[{\"image\":null,\"title\":\"Fee vouchers in one click\",\"text\":\"Vouchers for every family, late fees and sibling discounts worked out for you, and a list of who still owes.\",\"link\":\"\"},{\"image\":null,\"title\":\"Attendance in seconds\",\"text\":\"Everyone starts present. Tap the absent ones, and parents get a WhatsApp message.\",\"link\":\"\"},{\"image\":null,\"title\":\"Parents on their phone\",\"text\":\"Attendance, fees, results and homework, in English or Urdu.\",\"link\":\"\"},{\"image\":null,\"title\":\"Every student in one place\",\"text\":\"Families, siblings, classes and history. Bring them in from Excel.\",\"link\":\"\"},{\"image\":null,\"title\":\"Results and report cards\",\"text\":\"Marks by subject, positions and printed report cards.\",\"link\":\"\"},{\"image\":null,\"title\":\"A website for every school\",\"text\":\"Picture slider, campuses, gallery and an online admission form.\",\"link\":\"\"}]}',1,2,'2026-11-04 10:48:44','2026-11-04 10:48:44'),(3,1,'showcase','{\"_anchor\":\"timetable\",\"_eyebrow\":\"Timetable\",\"heading\":\"Drag, drop, done\",\"intro\":\"Build the timetable by dragging subjects onto the week. A teacher can never be put in two classes at once, and the system suggests what fits.\",\"address\":\"timetable\",\"_bg\":\"tint\"}',1,3,'2026-11-04 10:48:44','2026-11-04 10:48:44'),(4,1,'steps','{\"_anchor\":\"how\",\"_eyebrow\":\"How it works\",\"heading\":\"Up and running in a day\",\"items\":[{\"title\":\"Sign up\",\"text\":\"Your school is ready in a minute, with a 30-day free trial.\"},{\"title\":\"Bring in your students\",\"text\":\"Add classes, then import students and parents from Excel.\"},{\"title\":\"Send the first vouchers\",\"text\":\"Generate fee vouchers and invite parents to their portal.\"}]}',1,4,'2026-11-04 10:48:44','2026-11-04 10:48:44'),(5,1,'image_text','{\"_anchor\":\"parents\",\"_eyebrow\":\"For parents\",\"heading\":\"Parents always know how their child is doing\",\"body\":\"Attendance, results, fees and the daily diary in one portal, in English or Urdu. Absent alerts reach parents the same morning.\",\"image_side\":\"left\",\"highlights\":\"Parent portal\\nUrdu and English\\nAbsent alerts\\nReport cards\",\"_bg\":\"tint\"}',1,5,'2026-11-04 10:48:44','2026-11-04 10:48:44'),(6,1,'plans','{\"_anchor\":\"pricing\",\"_eyebrow\":\"Pricing\",\"heading\":\"Simple monthly plans\",\"intro\":\"Start with a free 30-day trial. Choose a plan when you are ready.\",\"note\":\"Prices in Pakistani rupees. Ask us about a yearly price or more than one campus.\",\"button_label\":\"Start free trial\",\"button_url\":\"\\/signup\"}',1,6,'2026-11-04 10:48:44','2026-11-04 10:48:44'),(7,1,'faq','{\"_anchor\":\"faq\",\"_eyebrow\":\"Questions\",\"heading\":\"Questions schools ask\",\"items\":[{\"question\":\"Do we need to install anything?\",\"answer\":\"No. It runs in the browser on a computer or a phone.\"},{\"question\":\"Does it work in Urdu?\",\"answer\":\"Yes. Parents can use their portal in Urdu or English.\"},{\"question\":\"Can parents pay fees online?\",\"answer\":\"Parents pay by bank, JazzCash or Easypaisa and upload a picture of the payment. The office checks it and marks the voucher paid.\"},{\"question\":\"Can other schools see our data?\",\"answer\":\"No. Every school only ever sees its own students, staff and fees.\"},{\"question\":\"What happens after the free trial?\",\"answer\":\"Choose a plan to keep going. Your data stays exactly as you left it.\"}]}',1,7,'2026-11-04 10:48:44','2026-11-04 10:48:44'),(8,1,'cta','{\"_eyebrow\":\"30-day free trial\",\"heading\":\"Give your school office its time back\",\"text\":\"Sign up and your school is ready in a minute. No card, no installation.\",\"button_label\":\"Start free trial\",\"button_url\":\"\\/signup\",\"_bg\":\"brand\",\"_spacing\":\"large\"}',1,8,'2026-11-04 10:48:44','2026-11-04 10:48:44'),(9,2,'hero','{\"layout\":\"center\",\"_eyebrow\":\"Features\",\"heading\":\"Everything your school needs\",\"text\":\"Each role sees only what it needs: the principal, the accountant, teachers and parents.\",\"_bg\":\"tint\"}',1,1,'2026-11-04 10:48:44','2026-11-04 10:48:44'),(10,2,'features','{\"columns\":\"3\",\"items\":[{\"image\":null,\"title\":\"Fees\",\"text\":\"Monthly vouchers, late fees, sibling discounts, payment proofs from parents and daily cash closing.\",\"link\":\"\"},{\"image\":null,\"title\":\"Attendance\",\"text\":\"Mark a class in seconds and queue WhatsApp messages to parents of absent students.\",\"link\":\"\"},{\"image\":null,\"title\":\"Exams and results\",\"text\":\"Enter marks by subject, publish results and print report cards.\",\"link\":\"\"},{\"image\":null,\"title\":\"Timetable\",\"text\":\"Drag and drop periods, with teacher clash checks and suggestions for what fits.\",\"link\":\"\"},{\"image\":null,\"title\":\"Admissions\",\"text\":\"A public apply form and a pipeline from enquiry to admission.\",\"link\":\"\"},{\"image\":null,\"title\":\"Early warning\",\"text\":\"Flags students at risk from attendance and results, with readable reasons.\",\"link\":\"\"},{\"image\":null,\"title\":\"Parent portal\",\"text\":\"Parents see attendance, results and fees, in English or Urdu.\",\"link\":\"\"},{\"image\":null,\"title\":\"Certificates and ID cards\",\"text\":\"Printed documents with a QR code anyone can verify.\",\"link\":\"\"},{\"image\":null,\"title\":\"Staff and payroll\",\"text\":\"Staff records, attendance, salary runs and salary slips.\",\"link\":\"\"},{\"image\":null,\"title\":\"Library and transport\",\"text\":\"Book loans, transport routes and which student uses them.\",\"link\":\"\"},{\"image\":null,\"title\":\"Finance\",\"text\":\"Expenses and an income and expense report.\",\"link\":\"\"},{\"image\":null,\"title\":\"Public website\",\"text\":\"Every school gets its own website, managed from its admin panel.\",\"link\":\"\"}]}',1,2,'2026-11-04 10:48:44','2026-11-04 10:48:44'),(11,2,'cta','{\"_eyebrow\":\"30-day free trial\",\"heading\":\"Give your school office its time back\",\"text\":\"Sign up and your school is ready in a minute. No card, no installation.\",\"button_label\":\"Start free trial\",\"button_url\":\"\\/signup\",\"_bg\":\"brand\",\"_spacing\":\"large\"}',1,3,'2026-11-04 10:48:44','2026-11-04 10:48:44'),(12,3,'contact','{\"heading\":\"Talk to us\",\"text\":\"Send a message and we will get back to you, usually the same day.\",\"show_form\":true,\"show_details\":true,\"show_map\":false}',1,1,'2026-11-04 10:48:44','2026-11-04 10:48:44'),(13,4,'hero','{\"_anchor\":\"top\",\"_eyebrow\":\"For any business that sells on installments\",\"layout\":\"product\",\"heading\":\"Installment sales and accounts, fully under control\",\"highlight\":\"fully under control\",\"text\":\"Sell motorcycles, phones, appliances, furniture or anything else on qist. Every installment, late customer, receipt and rupee in your books, in one simple system.\",\"button_label\":\"Start free trial\",\"button_url\":\"\\/signup?product=business\",\"button2_label\":\"See what it does\",\"button2_url\":\"\\/p\\/installments#features\",\"chips\":\"Any item, any plan\\nDouble-entry accounts\\nWhatsApp reminders\\nWorks on any phone\",\"_bg\":\"dark\",\"_spacing\":\"large\"}',1,1,'2026-11-04 10:48:45','2026-11-04 10:48:45'),(14,4,'features','{\"_anchor\":\"features\",\"_eyebrow\":\"Everything in one place\",\"heading\":\"From the sale to the last installment\",\"intro\":\"The way installment businesses already work, without the registers and the guesswork.\",\"style\":\"bento\",\"columns\":\"3\",\"items\":[{\"image\":null,\"title\":\"A sale in one minute\",\"text\":\"Cash price, markup, down payment and number of installments. The schedule and the monthly amount appear as you type.\",\"link\":\"\"},{\"image\":null,\"title\":\"Know who is late\",\"text\":\"Due today, late 1\\u201330, 31\\u201360, 61\\u201390 and over 90 days, with the amount and the days. Remind them or their guarantor on WhatsApp in one tap.\",\"link\":\"\"},{\"image\":null,\"title\":\"Receipts and agreements\",\"text\":\"Printed receipts for every payment, a customer statement, and an installment agreement with guarantors and terms, ready to sign.\",\"link\":\"\"},{\"image\":null,\"title\":\"Real accounts, done for you\",\"text\":\"Every sale and payment posts double-entry to your books. Markup counts as income only as it is paid, the way accountants do it.\",\"link\":\"\"},{\"image\":null,\"title\":\"Profit, balance sheet and more\",\"text\":\"Profit and loss, balance sheet, trial balance, ledgers, day book, collections and an aging of dues, at any date.\",\"link\":\"\"},{\"image\":null,\"title\":\"Guarantors and blacklist\",\"text\":\"Up to two guarantors on every sale, and a warning before selling again to a customer who did not pay.\",\"link\":\"\"}]}',1,2,'2026-11-04 10:48:45','2026-11-04 10:48:45'),(15,4,'steps','{\"_anchor\":\"how\",\"_eyebrow\":\"How it works\",\"heading\":\"Ready the same day\",\"items\":[{\"title\":\"Sign up\",\"text\":\"Choose \\\"Shop or business\\\". Your chart of accounts is set up for you.\"},{\"title\":\"Set your terms\",\"text\":\"Your usual markup, late fee, grace days and agreement terms.\"},{\"title\":\"Sell and collect\",\"text\":\"Make sales, take installments at the counter, and follow up the late ones.\"}],\"_bg\":\"tint\"}',1,3,'2026-11-04 10:48:45','2026-11-04 10:48:45'),(16,4,'features','{\"_eyebrow\":\"All features\",\"heading\":\"Everything an installment business needs\",\"columns\":\"3\",\"items\":[{\"image\":null,\"title\":\"Customers\",\"text\":\"Name, CNIC, phones, address, photo and every plan they have had.\",\"link\":\"\"},{\"image\":null,\"title\":\"Flexible plans\",\"text\":\"Monthly, every two weeks or weekly. Markup as a percent or a fixed amount. Installments rounded to 10, 50 or 100.\",\"link\":\"\"},{\"image\":null,\"title\":\"Counter collection\",\"text\":\"Search by name, phone, CNIC or plan number. The amount due and the late fee are filled in.\",\"link\":\"\"},{\"image\":null,\"title\":\"Late fees and discounts\",\"text\":\"A late fee per late installment after your grace days, and discounts for early payment.\",\"link\":\"\"},{\"image\":null,\"title\":\"Cancel safely\",\"text\":\"A wrong receipt is cancelled with a reason. Its entry is reversed, never deleted.\",\"link\":\"\"},{\"image\":null,\"title\":\"Bad debts\",\"text\":\"Write off what cannot be recovered. The unearned markup is cancelled automatically.\",\"link\":\"\"},{\"image\":null,\"title\":\"Expenses and vouchers\",\"text\":\"Pay rent, salaries and bills; record capital and loans; make journal entries.\",\"link\":\"\"},{\"image\":null,\"title\":\"Staff logins\",\"text\":\"An owner login and accountant logins, with every change in the audit log.\",\"link\":\"\"},{\"image\":null,\"title\":\"Your own website\",\"text\":\"A website for your shop, managed from your panel.\",\"link\":\"\"}]}',1,4,'2026-11-04 10:48:45','2026-11-04 10:48:45'),(17,4,'plans','{\"_anchor\":\"pricing\",\"product\":\"business\",\"_eyebrow\":\"Pricing\",\"heading\":\"One simple monthly price\",\"intro\":\"Start with a free trial. Choose a plan when you are ready.\",\"note\":\"Prices in Pakistani rupees. Ask us about more than one branch.\",\"button_label\":\"Start free trial\",\"button_url\":\"\\/signup?product=business\",\"_bg\":\"tint\"}',1,5,'2026-11-04 10:48:45','2026-11-04 10:48:45'),(18,4,'faq','{\"_anchor\":\"faq\",\"_eyebrow\":\"Questions\",\"heading\":\"Questions businesses ask\",\"items\":[{\"question\":\"What can I sell on installments?\",\"answer\":\"Anything: motorcycles, mobiles, LED TVs, fridges, furniture, solar systems, even plots. You type the item and its details.\"},{\"question\":\"How is the markup worked out?\",\"answer\":\"As a percent of what is left after the down payment, or as a fixed amount you enter. The total, the installment and the schedule are shown before you save.\"},{\"question\":\"Do I need to know accounting?\",\"answer\":\"No. Sales and payments post to the right accounts by themselves. Your accountant still gets proper ledgers, a trial balance, profit and loss and a balance sheet.\"},{\"question\":\"Can I remind customers on WhatsApp?\",\"answer\":\"Yes. One tap opens WhatsApp with the reminder written for you, to the customer or a guarantor. You can also queue reminders for everyone who is late.\"},{\"question\":\"Can other businesses see my data?\",\"answer\":\"No. Every business only ever sees its own customers and accounts.\"}]}',1,6,'2026-11-04 10:48:45','2026-11-04 10:48:45'),(19,4,'cta','{\"_eyebrow\":\"Free trial\",\"heading\":\"Know every rupee your customers owe\",\"text\":\"Sign up and your business is ready in a minute. No card, no installation.\",\"button_label\":\"Start free trial\",\"button_url\":\"\\/signup?product=business\",\"_bg\":\"brand\",\"_spacing\":\"large\"}',1,7,'2026-11-04 10:48:45','2026-11-04 10:48:45');
/*!40000 ALTER TABLE `site_sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sites`
--

DROP TABLE IF EXISTS `sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_key` varchar(255) NOT NULL,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `subdomain` varchar(63) DEFAULT NULL,
  `domain` varchar(190) DEFAULT NULL,
  `domain_status` varchar(10) NOT NULL DEFAULT 'none',
  `domain_check` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`domain_check`)),
  `domain_registrar` varchar(100) DEFAULT NULL,
  `domain_expires_on` date DEFAULT NULL,
  `domain_notes` text DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sites_owner_key_unique` (`owner_key`),
  UNIQUE KEY `sites_subdomain_unique` (`subdomain`),
  UNIQUE KEY `sites_domain_unique` (`domain`),
  KEY `sites_school_id_foreign` (`school_id`),
  CONSTRAINT `sites_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sites`
--

LOCK TABLES `sites` WRITE;
/*!40000 ALTER TABLE `sites` DISABLE KEYS */;
INSERT INTO `sites` VALUES (1,'platform',NULL,NULL,NULL,'none',NULL,NULL,NULL,NULL,1,'{\"design\":{\"site_name\":\"MSK Devs\",\"primary\":\"#4f46e5\",\"accent\":\"#f97316\",\"seo_description\":\"School and college management: fees, attendance, results, timetable and a parent portal in one system.\"},\"header\":{\"cta_show\":true,\"cta_label\":\"Start free trial\",\"cta_url\":\"\\/signup\"},\"footer\":{\"about\":\"School and college management software.\"}}','2026-11-04 10:48:44','2026-11-04 10:48:44');
/*!40000 ALTER TABLE `sites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `category` varchar(20) NOT NULL DEFAULT 'teaching',
  `duty_starts_at` varchar(5) DEFAULT NULL,
  `duty_ends_at` varchar(5) DEFAULT NULL,
  `biometric_id` varchar(20) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `cnic` varchar(255) DEFAULT NULL,
  `joined_on` date DEFAULT NULL,
  `basic_salary` int(10) unsigned NOT NULL DEFAULT 0,
  `allowance` int(10) unsigned NOT NULL DEFAULT 0,
  `is_teacher` tinyint(1) NOT NULL DEFAULT 1,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_user_id_foreign` (`user_id`),
  KEY `staff_school_id_biometric_id_index` (`school_id`,`biometric_id`),
  CONSTRAINT `staff_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_attendances`
--

DROP TABLE IF EXISTS `staff_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_attendances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `status` char(1) NOT NULL,
  `check_in` varchar(5) DEFAULT NULL,
  `check_out` varchar(5) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `source` varchar(10) NOT NULL DEFAULT 'office',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_attendances_staff_id_date_unique` (`staff_id`,`date`),
  KEY `staff_attendances_school_id_foreign` (`school_id`),
  CONSTRAINT `staff_attendances_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_attendances_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_attendances`
--

LOCK TABLES `staff_attendances` WRITE;
/*!40000 ALTER TABLE `staff_attendances` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_attendances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `guardian_id` bigint(20) unsigned NOT NULL,
  `section_id` bigint(20) unsigned NOT NULL,
  `transport_route_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `admission_no` varchar(255) NOT NULL,
  `roll_no` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `father_name` varchar(255) DEFAULT NULL,
  `gender` varchar(10) NOT NULL DEFAULT 'male',
  `dob` date DEFAULT NULL,
  `b_form` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `admitted_on` date DEFAULT NULL,
  `monthly_concession` int(10) unsigned NOT NULL DEFAULT 0,
  `concession_reason` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `left_on` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_school_id_admission_no_unique` (`school_id`,`admission_no`),
  KEY `students_guardian_id_foreign` (`guardian_id`),
  KEY `students_section_id_foreign` (`section_id`),
  KEY `students_transport_route_id_foreign` (`transport_route_id`),
  KEY `students_user_id_foreign` (`user_id`),
  KEY `students_school_id_status_index` (`school_id`,`status`),
  CONSTRAINT `students_guardian_id_foreign` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`),
  CONSTRAINT `students_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `students_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`),
  CONSTRAINT `students_transport_route_id_foreign` FOREIGN KEY (`transport_route_id`) REFERENCES `transport_routes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subjects_school_id_name_unique` (`school_id`,`name`),
  CONSTRAINT `subjects_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_entries`
--

DROP TABLE IF EXISTS `subscription_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `type` varchar(10) NOT NULL,
  `months` smallint(5) unsigned NOT NULL DEFAULT 0,
  `amount` int(10) unsigned NOT NULL DEFAULT 0,
  `from_date` date DEFAULT NULL,
  `to_date` date NOT NULL,
  `paid_on` date DEFAULT NULL,
  `method` varchar(20) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscription_entries_school_id_foreign` (`school_id`),
  KEY `subscription_entries_user_id_foreign` (`user_id`),
  CONSTRAINT `subscription_entries_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscription_entries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_entries`
--

LOCK TABLES `subscription_entries` WRITE;
/*!40000 ALTER TABLE `subscription_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscription_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timetable_entries`
--

DROP TABLE IF EXISTS `timetable_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timetable_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `section_id` bigint(20) unsigned NOT NULL,
  `timetable_period_id` bigint(20) unsigned NOT NULL,
  `day` tinyint(3) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `staff_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tt_section_slot` (`section_id`,`timetable_period_id`,`day`),
  UNIQUE KEY `tt_teacher_slot` (`staff_id`,`timetable_period_id`,`day`),
  KEY `timetable_entries_school_id_foreign` (`school_id`),
  KEY `timetable_entries_timetable_period_id_foreign` (`timetable_period_id`),
  KEY `timetable_entries_subject_id_foreign` (`subject_id`),
  CONSTRAINT `timetable_entries_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_entries_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_entries_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `timetable_entries_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_entries_timetable_period_id_foreign` FOREIGN KEY (`timetable_period_id`) REFERENCES `timetable_periods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timetable_entries`
--

LOCK TABLES `timetable_entries` WRITE;
/*!40000 ALTER TABLE `timetable_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `timetable_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timetable_periods`
--

DROP TABLE IF EXISTS `timetable_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timetable_periods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `timetable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `starts_at` varchar(5) NOT NULL,
  `ends_at` varchar(5) NOT NULL,
  `is_break` tinyint(1) NOT NULL DEFAULT 0,
  `sort` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timetable_periods_school_id_foreign` (`school_id`),
  KEY `timetable_periods_timetable_id_foreign` (`timetable_id`),
  CONSTRAINT `timetable_periods_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_periods_timetable_id_foreign` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timetable_periods`
--

LOCK TABLES `timetable_periods` WRITE;
/*!40000 ALTER TABLE `timetable_periods` DISABLE KEYS */;
/*!40000 ALTER TABLE `timetable_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timetables`
--

DROP TABLE IF EXISTS `timetables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timetables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `timetables_school_id_name_unique` (`school_id`,`name`),
  CONSTRAINT `timetables_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timetables`
--

LOCK TABLES `timetables` WRITE;
/*!40000 ALTER TABLE `timetables` DISABLE KEYS */;
/*!40000 ALTER TABLE `timetables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transport_routes`
--

DROP TABLE IF EXISTS `transport_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transport_routes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `vehicle_no` varchar(255) DEFAULT NULL,
  `driver_name` varchar(255) DEFAULT NULL,
  `driver_phone` varchar(255) DEFAULT NULL,
  `monthly_fee` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transport_routes_school_id_foreign` (`school_id`),
  CONSTRAINT `transport_routes_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transport_routes`
--

LOCK TABLES `transport_routes` WRITE;
/*!40000 ALTER TABLE `transport_routes` DISABLE KEYS */;
/*!40000 ALTER TABLE `transport_routes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'admin',
  `phone` varchar(255) DEFAULT NULL,
  `locale` varchar(5) NOT NULL DEFAULT 'en',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `email` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_school_id_foreign` (`school_id`),
  CONSTRAINT `users_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,NULL,'Sajjad','itxsajjad786@gmail.com','super_admin',NULL,'en',1,1,'itxsajjad786@gmail.com',NULL,'$2y$12$3cRKTl2E.jkS3N9poeO0oupFB8Hh9elCL./mFDQ8u9/2p4OMS53Wq',NULL,NULL,'2026-11-04 10:48:44','2026-11-04 10:48:44');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-04 15:48:45
