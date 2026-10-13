-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: pet_rescue_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Current Database: `pet_rescue_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `pet_rescue_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `pet_rescue_db`;

--
-- Table structure for table `adoption_applications`
--

DROP TABLE IF EXISTS `adoption_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `adoption_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `adoption_post_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `message` text NOT NULL,
  `id_proof_path` varchar(255) NOT NULL,
  `agreement_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `agreement_timestamp` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `adoption_post_id` (`adoption_post_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `adoption_applications`
--

LOCK TABLES `adoption_applications` WRITE;
/*!40000 ALTER TABLE `adoption_applications` DISABLE KEYS */;
INSERT INTO `adoption_applications` VALUES (2,20,8,'Pratham','7760206717','2B Cross Road Kodical Mangalore','I want to adopt this pet because I love animals and want to give a safe and caring home to a local dog. Desi dogs are loyal, intelligent, and often overlooked, so adopting one is a way to support and care for them. I also believe having a pet brings happiness, companionship, and a sense of responsibility in life.','uploads/id_proofs/1774284690_id_stock-vector-dummy-aadhar-card-unique-identity-document-for-indian-citizen-issued-by-government-of-india-vector-1661857768.jpg',1,'2026-03-23 16:51:30','rejected','2026-03-23 16:51:30'),(3,21,1,'Manisha','8523697410','Near Kadri Temple, Mangalore','I want to adopt this pet because I love animals and want to give a safe and caring home to a local dog. Desi dogs are loyal, intelligent, and often overlooked, so adopting one is a way to support and care for them. I also believe having a pet brings happiness, companionship, and a sense of responsibility in life.','uploads/id_proofs/1774285423_id_stock-vector-dummy-aadhar-card-unique-identity-document-for-indian-citizen-issued-by-government-of-india-vector-1661857768.jpg',1,'2026-03-23 17:03:43','pending','2026-03-23 17:03:43'),(4,20,7,'Pratham','7788994455','2B Cross Road Kodical Mangalore','I want to adopt this pet because I love animals and want to give a safe and caring home to a local dog. Desi dogs are loyal, intelligent, and often overlooked, so adopting one is a way to support and care for them. I also believe having a pet brings happiness, companionship, and a sense of responsibility in life.','uploads/id_proofs/1774285832_id_stock-vector-dummy-aadhar-card-unique-identity-document-for-indian-citizen-issued-by-government-of-india-vector-1661857768.jpg',1,'2026-03-23 17:10:32','approved','2026-03-23 17:10:32');
/*!40000 ALTER TABLE `adoption_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `adoption_posts`
--

DROP TABLE IF EXISTS `adoption_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `adoption_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `pet_name` varchar(100) NOT NULL,
  `pet_type` varchar(50) NOT NULL,
  `gender` enum('Male','Female','Unknown') NOT NULL DEFAULT 'Unknown',
  `breed` varchar(100) DEFAULT NULL,
  `age` varchar(20) NOT NULL,
  `description` text NOT NULL,
  `photo` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `adoption_status` enum('available','adopted') NOT NULL DEFAULT 'available',
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `adoption_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `adoption_posts`
--

LOCK TABLES `adoption_posts` WRITE;
/*!40000 ALTER TABLE `adoption_posts` DISABLE KEYS */;
INSERT INTO `adoption_posts` VALUES (1,20,'Misty','Cat','Female','Mixed Breed','2 years','Playful and friendly. She is partially vaccinated and is good with kids. Misty eats fish, rice, and cat food.','uploads/1770354393_adopt_Screenshot 2026-02-06 103509.png','approved','2026-02-06 05:06:33','available',0),(2,20,'Shadow','Cat','Male','Domestic Shorthair','1 year','A quiet and cautious nature. He is partially vaccinated and is good with kids if treated kindly. Shadow eats leftover food, fish, and cat food.','uploads/1770354472_adopt_Screenshot 2026-02-06 103439.png','approved','2026-02-06 05:07:52','available',0),(3,21,'Brownie','Dog','Female','Mixed Breed','3 years','Calm and protective. She is partially vaccinated and is good with kids. Brownie eats rice, vegetables, and leftover food.','uploads/1770354553_adopt_Screenshot 2026-02-06 103411.png','approved','2026-02-06 05:09:13','available',0),(4,21,'Tommy','Dog','Male','Indian Pariah Dog','5 years','A friendly and alert nature. He is partially vaccinated and is good with kids, but needs gentle care. Tommy eats rice, leftovers, meat, and dog food when available.','uploads/1770354667_adopt_Screenshot 2026-02-06 103309.png','approved','2026-02-06 05:11:07','available',0),(7,27,'Rocky','Dog','Male','German Shepherd','3 years','Energetic, intelligent, and protective. He is fully vaccinated and is good with kids. Rocky eats dog food, meat, and rice.','uploads/1770355041_adopt_Screenshot 2026-02-06 102928.png','approved','2026-02-06 05:17:21','adopted',0),(8,27,'Buddy','Dog','Female','Golden Retriever','2 years','Dog with a friendly and playful nature. He is fully vaccinated and is very good with kids because he is gentle and patient. Buddy eats dog food along with rice, chicken, and vegetables.','uploads/1770355155_adopt_Screenshot 2026-02-06 104807.png','approved','2026-02-06 05:19:15','available',0),(12,20,'Daisy','Dog','Male','Golden Retriever','2 years','Gentle, loving, and very friendly. Enjoys spending time with family and is calm in most situations. Obedient and social. Gets along well with kids and other pets. Loves playing fetch and going on walks. Fully vaccinated and regularly checked by a veterinarian. Vaccines include rabies, parvovirus, and distemper.','uploads/1774284555_adopt_Screenshot 2026-03-23 221859.png','pending','2026-03-23 16:49:15','available',0);
/*!40000 ALTER TABLE `adoption_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `vet_salon_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `status` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `vet_salon_id` (`vet_salon_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`vet_salon_id`) REFERENCES `vet_salon` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_requests`
--

DROP TABLE IF EXISTS `contact_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ngo_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_requests`
--

LOCK TABLES `contact_requests` WRITE;
/*!40000 ALTER TABLE `contact_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donations`
--

DROP TABLE IF EXISTS `donations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ngo_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` varchar(50) NOT NULL DEFAULT 'completed',
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `ngo_id` (`ngo_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donations`
--

LOCK TABLES `donations` WRITE;
/*!40000 ALTER TABLE `donations` DISABLE KEYS */;
INSERT INTO `donations` VALUES (1,1,1,50000.00,'completed','2026-02-06 05:28:17'),(2,26,4,5212.00,'completed','2026-02-06 17:36:09'),(3,26,1,7418.00,'completed','2026-02-06 17:37:06'),(4,20,4,12588.00,'completed','2026-03-23 17:20:44'),(5,21,4,48962.00,'completed','2026-03-23 17:41:02');
/*!40000 ALTER TABLE `donations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `followup_updates`
--

DROP TABLE IF EXISTS `followup_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `followup_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `followup_id` int(11) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `message` text NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `followup_id` (`followup_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `followup_updates`
--

LOCK TABLES `followup_updates` WRITE;
/*!40000 ALTER TABLE `followup_updates` DISABLE KEYS */;
INSERT INTO `followup_updates` VALUES (1,1,'uploads/followups/1774285967_fu_Screenshot 2026-02-06 102928.png',12.89082100,74.84334450,'Its good enjoying time with me','2026-03-23 17:12:47');
/*!40000 ALTER TABLE `followup_updates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ngos`
--

DROP TABLE IF EXISTS `ngos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ngos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ngo_name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `services` text NOT NULL,
  `registration_certificate` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `ngos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ngos`
--

LOCK TABLES `ngos` WRITE;
/*!40000 ALTER TABLE `ngos` DISABLE KEYS */;
INSERT INTO `ngos` VALUES (1,24,'Charlie\'s Animal Rescue Centre (CARE)','A well-known rescue and shelter centre dedicated to rescuing injured, sick and homeless animals, providing care, shelter and adoption support.','Survey No. 124/1 Mittaganahalli Cross, Kogilu, Near Reva College, Yelahanka','Bengaluru','Adoption support, rescuing injured.','uploads/certificates/cert_1770351997_69856d7da56d8.png','approved','2026-02-06 04:26:37'),(4,27,'People for Animals Wildlife Rescue & Conservation Centre (Bengaluru PfA)','Part of a national animal welfare network focused on rescue, rehabilitation and release of urban wildlife and street animals.','Sunkalpalya','Bengaluru','Rescue, Adoption, Shelter','uploads/certificates/cert_1770352252_69856e7c3c2cb.png','approved','2026-02-06 04:30:52'),(5,28,'Friend For Animal Trust','A community-oriented animal rescue organisation that works on rescuing and caring for street animals and promoting welfare.','Balli compound, Kodical','Mangalore','providing care, shelter and adoption support','uploads/certificates/cert_1770352336_69856ed017888.png','approved','2026-02-06 04:32:16'),(6,29,'Karuna Animal Shelter','Historic animal welfare association providing shelter and humane services.','Near Veterinary college campus','Mysore','Adoption support, rescuing injured.','uploads/certificates/cert_1770353551_6985738f91450.png','approved','2026-02-06 04:52:31'),(7,30,'Blue Cross of India','The Blue Cross of India (BCI) is a leading animal welfare organization based in Chennai, Tamil Nadu. Founded in 1959 and formally registered in 1964, it is among India’s oldest and largest animal welfare NGOs, known for pioneering humane approaches to managing street-animal populations and advancing veterinary care and education nationwide.','Chennai, Tamil Nadu, India','Chennai','Animal rescue, shelter, veterinary services, humane education, and animal birth control','uploads/certificates/cert_1774286222_69c1758e096c2.png','pending','2026-03-23 17:17:02');
/*!40000 ALTER TABLE `ngos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ownership_followups`
--

DROP TABLE IF EXISTS `ownership_followups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ownership_followups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pet_id` int(11) NOT NULL,
  `adopter_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','completed','overdue') NOT NULL DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pet_id` (`pet_id`),
  KEY `adopter_id` (`adopter_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ownership_followups`
--

LOCK TABLES `ownership_followups` WRITE;
/*!40000 ALTER TABLE `ownership_followups` DISABLE KEYS */;
INSERT INTO `ownership_followups` VALUES (1,7,20,'2026-07-23','completed','2026-03-23 17:10:55'),(2,7,20,'2026-07-23','pending','2026-03-23 17:12:47');
/*!40000 ALTER TABLE `ownership_followups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pet_ownership`
--

DROP TABLE IF EXISTS `pet_ownership`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pet_ownership` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pet_id` int(11) NOT NULL,
  `adopter_id` int(11) NOT NULL,
  `adoption_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','returned') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `pet_id` (`pet_id`),
  KEY `adopter_id` (`adopter_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pet_ownership`
--

LOCK TABLES `pet_ownership` WRITE;
/*!40000 ALTER TABLE `pet_ownership` DISABLE KEYS */;
INSERT INTO `pet_ownership` VALUES (1,7,20,'2026-03-23 17:10:55','active');
/*!40000 ALTER TABLE `pet_ownership` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rescue_requests`
--

DROP TABLE IF EXISTS `rescue_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rescue_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `guest_contact` varchar(20) DEFAULT NULL,
  `animal_type` varchar(50) NOT NULL,
  `condition_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `status` enum('pending','in_progress','rescued') NOT NULL DEFAULT 'pending',
  `assigned_ngo_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `assigned_ngo_id` (`assigned_ngo_id`),
  CONSTRAINT `rescue_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rescue_requests_ibfk_2` FOREIGN KEY (`assigned_ngo_id`) REFERENCES `ngos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rescue_requests`
--

LOCK TABLES `rescue_requests` WRITE;
/*!40000 ALTER TABLE `rescue_requests` DISABLE KEYS */;
INSERT INTO `rescue_requests` VALUES (1,20,NULL,NULL,'Dog','Injured','Met with an accident and its leg is fractured','Lat: 12.8764651, Lng: 74.8585658','uploads/1770355842_Screenshot 2026-02-02 235033.png','rescued',1,'2026-02-06 05:30:42',12.87646510,74.85856580),(2,NULL,'Rahul','9855536210','Dog','Injured','Dog has been hit by a car','SH1, Siralkoppa, Shikaripura taluk, Shimoga, Karnataka, 577428, India','uploads/1774163666_Screenshot 2026-02-02 234944.png','rescued',NULL,'2026-03-22 07:14:26',14.38090000,75.24810000),(3,21,NULL,NULL,'Dog','Injured','Dog has been hit by a vehicle','SH1, Siralkoppa, Shikaripura taluk, Shimoga, Karnataka, 577428, India','uploads/1774163923_Screenshot 2026-02-02 235033.png','rescued',1,'2026-03-22 07:18:43',14.38090000,75.24810000),(4,20,NULL,NULL,'Dog','Injured','Dog was hit by a car and is been injured','B 2nd Cross, Urva Store, Kodikal, Mangaluru taluk, Dakshina Kannada, Karnataka, 575001, India','uploads/1774284259_Screenshot 2026-02-02 235033.png','pending',NULL,'2026-03-23 16:44:19',12.90475500,74.82865500),(5,20,NULL,NULL,'Dog','Injured','A dog was hit','Kadri, Mangaluru, Mangaluru taluk, Dakshina Kannada, Karnataka, 575001, India','uploads/1774326369_Screenshot 2026-02-02 234944.png','in_progress',4,'2026-03-24 04:26:09',12.89082100,74.84334450);
/*!40000 ALTER TABLE `rescue_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rescue_status_logs`
--

DROP TABLE IF EXISTS `rescue_status_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rescue_status_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rescue_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rescue_status_logs`
--

LOCK TABLES `rescue_status_logs` WRITE;
/*!40000 ALTER TABLE `rescue_status_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `rescue_status_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `role` enum('user','ngo','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Super Admin','admin@petrescue.com','$2y$10$p.q6XXcmq69u1yxvqhoU4uWq33kYU0WtBy.AWz8is2CJTx6//Ks5O','1234567890','admin','2026-02-03 09:56:20',0),(20,'Pratham','pratham@gmail.com','$2y$10$5KJBbm/iDacO0QkMoqBoU.CY380LHmogjoYa06hGTzPf/g4g.lZqq','7760206717','user','2026-02-06 04:09:02',0),(21,'Manisha','manisha@gmail.com','$2y$10$kAdn4OD6qIIib5VzdkQkwu6JlrUHJwpQ6wJIepYPBhZ5aIZDvaqWy','8885552221','user','2026-02-06 04:09:34',0),(24,'Harish','charliengo@gmail.com','$2y$10$LIlO2Aw5ZauwT53HjTHUZeG70IyK3qRTIiXgqwAOzgUn2EIB5nvD6','8889995555','ngo','2026-02-06 04:26:37',0),(27,'Lokesh','pfango@gmail.com','$2y$10$GBTqj6.jkZI9PQjgAY9f3O9iufGna3HclGBwO8rNVQKWTLxhn4jmu','8529637410','ngo','2026-02-06 04:30:52',0),(28,'Rakesh','friendngo@gmail.com','$2y$10$1fBvxWQvo6WYy6JWABH9muJFqk2zc72lghuPvTcNBvZBYhINjp9Ty','8523697410','ngo','2026-02-06 04:32:16',0),(29,'Rahul','karunango@gmail.com','$2y$10$8pFyNVZCr1AeB47ykKM0b.Bdft3NNCjmhQAL.SwGNdxQ.YedQ3ebi','7894445556','ngo','2026-02-06 04:52:31',0),(30,'Hithesh','bluengo@gmail.com','$2y$10$C7xSOkcqICqVzfWVZwSLmOEIodqhADEklUVPRsPdddX.DYPgQmvvq','8529637400','ngo','2026-03-23 17:17:02',0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vet_salon`
--

DROP TABLE IF EXISTS `vet_salon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vet_salon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('vet','salon') NOT NULL,
  `location` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `services` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vet_salon`
--

LOCK TABLES `vet_salon` WRITE;
/*!40000 ALTER TABLE `vet_salon` DISABLE KEYS */;
INSERT INTO `vet_salon` VALUES (1,'Bark In Style - Professional Dog Grooming Spa','salon','Sriram Arcade, Seegehalli Rd, near Axis Bank, Opp. Nitesh Flushing Meadows, Whitefield, Kadugodi, Bengaluru, Karnataka 560115, India','8523697410','Highly rated grooming services','2026-02-03 15:49:19',0),(2,'Wiggle Waggle Pet Grooming Studio','salon','hop No. 3, Opposite Haj Bhawan, Agrahara Badavane, Thirumenahalli, Bengaluru, Karnataka 560064,','8794563210','Grooming and pampering services','2026-02-03 15:50:33',0),(3,'Fuzzy Wuzzy - Pet Styling Studio & Spa','salon','Opp. Metro Pillar No. 89, Hoysala Nagar, Indiranagar, Bengaluru, Karnataka 560008, India','8794563210','Grooming and Spa','2026-02-03 15:51:32',0),(4,'Chasing Tails Pet Hospital','vet','Whitefield Main Rd, NGEF ANCILLARY, Industrial Area, Mahadevapura, Bengaluru, Karnataka 560048, India','8794561583','Full-service vet hospital (check services & hours).','2026-02-03 15:52:12',0),(5,'Anu Pet Clinic','vet','2nd \'A\' Main Street, 3, 8th Cross Rd, Ramaiah Layout, Kammanahalli, Bengaluru, Karnataka 560084, India','7778889991','Popular local vet clinic for routine care','2026-02-03 15:57:06',0),(6,'Lucky Paws Veterinary Hospital, Budigere Cross Branch','vet','Apartment off Brigade Buena Vista, Old Madras Rd, Opp. Road, Budigere Cross, Bommenahalli, Bengaluru, Karnataka 560049, India','8889991112','Multi-speciality veterinary hospital.','2026-02-03 15:57:43',0);
/*!40000 ALTER TABLE `vet_salon` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-13 11:20:52
