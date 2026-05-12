-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 10 mai 2026 à 12:06
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `caremeal`
--

-- --------------------------------------------------------

--
-- Structure de la table `categorie`
--

CREATE TABLE `categorie` (
  `id_categorie` int(10) UNSIGNED NOT NULL,
  `nom` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categorie`
--

INSERT INTO `categorie` (`id_categorie`, `nom`, `description`, `actif`, `created_at`, `updated_at`) VALUES
(2, 'Boulangerie', 'pains, croissants', 1, '2026-05-10 00:04:10', '2026-05-10 01:06:51'),
(3, 'Desserts', 'gateau, cake', 1, '2026-05-10 00:04:34', '2026-05-10 09:14:56'),
(4, 'soupe', 'tomate...', 1, '2026-05-10 00:05:17', '2026-05-10 00:05:17'),
(5, 'salades', '', 1, '2026-05-10 00:07:20', '2026-05-10 00:07:20');

-- --------------------------------------------------------

--
-- Structure de la table `categorie_offre`
--

CREATE TABLE `categorie_offre` (
  `id_categorie` int(11) NOT NULL,
  `nom_categorie` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `icone` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categorie_offre`
--

INSERT INTO `categorie_offre` (`id_categorie`, `nom_categorie`, `description`, `icone`) VALUES
(61, 'Pizza', 'Pizza', NULL),
(62, 'Fruits', 'BANAN', NULL),
(64, 'Pizza', NULL, NULL),
(66, 'Sandwich', 'Sandwich délicieux', NULL),
(67, 'banane', NULL, NULL),
(69, 'Sandwich', NULL, NULL),
(70, 'Desserts', 'Desserts délicieux', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `commande`
--

CREATE TABLE `commande` (
  `id_commande` int(10) UNSIGNED NOT NULL,
  `id_produit` int(10) UNSIGNED NOT NULL,
  `id_user` varchar(100) NOT NULL,
  `quantite` int(10) UNSIGNED NOT NULL,
  `prix_unitaire_snapshot` decimal(10,2) NOT NULL,
  `prix_normal_snapshot` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `statut` enum('en_attente','validee','retiree','annulee') NOT NULL DEFAULT 'en_attente',
  `date_commande` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commande`
--

INSERT INTO `commande` (`id_commande`, `id_produit`, `id_user`, `quantite`, `prix_unitaire_snapshot`, `prix_normal_snapshot`, `total`, `statut`, `date_commande`) VALUES
(1, 3, '8', 1, 3.00, 5.00, 3.00, 'en_attente', '2026-05-10 01:10:53'),
(2, 3, '8', 1, 3.00, 5.00, 3.00, 'retiree', '2026-05-10 01:15:14'),
(3, 6, '8', 1, 1.00, 1.50, 1.00, 'en_attente', '2026-05-10 01:19:27'),
(4, 1, '8', 1, 0.30, 0.80, 0.30, 'annulee', '2026-05-10 01:23:23'),
(12, 1, '7', 1, 0.30, 0.80, 0.30, 'validee', '2026-05-10 06:00:23'),
(13, 1, '8', 1, 0.30, 0.80, 0.30, 'validee', '2026-05-10 06:29:35'),
(16, 6, '8', 1, 1.00, 1.50, 1.00, 'validee', '2026-05-10 06:36:58'),
(18, 1, '8', 1, 0.30, 0.80, 0.30, 'annulee', '2026-05-10 08:43:31');

-- --------------------------------------------------------

--
-- Structure de la table `commander`
--

CREATE TABLE `commander` (
  `id_commande` int(11) NOT NULL,
  `id_offre` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `statut` enum('en_attente','confirmee','annulee','recuperee') DEFAULT 'en_attente',
  `date_commande` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commander`
--

INSERT INTO `commander` (`id_commande`, `id_offre`, `nom`, `prenom`, `email`, `telephone`, `quantite`, `statut`, `date_commande`) VALUES
(1, 139, 'Ben Salah', 'Hadil', 'hadil.bensalah@esprit.tn', '28257013', 1, 'confirmee', '2026-05-10 10:15:18'),
(2, 139, 'Ben Salah', 'Hadil', 'hadil.bensalah@esprit.tn', '28257013', 1, 'annulee', '2026-05-10 10:19:11'),
(3, 128, 'Ben Salah', 'Hadil', 'hadil.bensalah@esprit.tn', '28257013', 1, 'recuperee', '2026-05-10 10:21:56');

-- --------------------------------------------------------

--
-- Structure de la table `commentaire`
--

CREATE TABLE `commentaire` (
  `id_commentaire` int(11) NOT NULL,
  `contenu_commentaire` text NOT NULL,
  `date_commentaire` datetime DEFAULT current_timestamp(),
  `id_publication` int(11) NOT NULL,
  `auteur_type` enum('etudiant','partenaire') NOT NULL,
  `auteur_id` varchar(50) DEFAULT NULL,
  `moderation_status` varchar(20) NOT NULL DEFAULT 'approved',
  `moderation_risk` varchar(20) DEFAULT NULL,
  `moderation_reason` varchar(255) DEFAULT NULL,
  `moderation_provider` varchar(50) NOT NULL DEFAULT 'gemini'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commentaire_reaction`
--

CREATE TABLE `commentaire_reaction` (
  `id` int(11) NOT NULL,
  `commentaire_id` int(11) NOT NULL,
  `auteur_type` varchar(50) NOT NULL,
  `auteur_id` varchar(50) NOT NULL,
  `reaction` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `evenement`
--

CREATE TABLE `evenement` (
  `id_evenement` int(11) NOT NULL,
  `titre` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `date_evenement` date NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `type_evenement` varchar(20) NOT NULL DEFAULT 'Présentiel',
  `lieu` varchar(150) DEFAULT NULL,
  `lien_online` varchar(255) DEFAULT NULL,
  `capacite_max` int(11) NOT NULL DEFAULT 0,
  `statut` varchar(20) NOT NULL DEFAULT 'Planifié',
  `createur_type` varchar(20) NOT NULL,
  `createur_id` int(11) NOT NULL,
  `statut_validation` varchar(20) NOT NULL DEFAULT 'En attente',
  `motif_refus` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `evenement`
--

INSERT INTO `evenement` (`id_evenement`, `titre`, `description`, `date_evenement`, `heure_debut`, `heure_fin`, `type_evenement`, `lieu`, `lien_online`, `capacite_max`, `statut`, `createur_type`, `createur_id`, `statut_validation`, `motif_refus`) VALUES
(8, 'aziz', 'sds', '2026-05-21', '19:45:00', '20:45:00', 'Présentiel', 'hawariyaaaa', '', 17, 'Planifié', 'Partenaire', 3, 'Validé', NULL),
(9, 'esprit', 'vb', '2026-06-06', '18:45:00', '20:45:00', 'En ligne', '', 'https://www.monnuage.fr/a-voir/tunisie/nabeul/hawariya', 35, 'Planifié', 'Partenaire', 3, 'Rejeté', 'probleme'),
(10, 'atestation', 'sport', '2026-05-31', '01:23:00', '02:23:00', 'Présentiel', 'Entrepôt Logistique CareMeal, Zone Industrielle.', '', 3, 'Planifié', 'Partenaire', 3, 'Validé', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `matching_snapshot`
--

CREATE TABLE `matching_snapshot` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_pref` int(11) NOT NULL,
  `price_mode` varchar(16) NOT NULL,
  `sort_by` varchar(16) NOT NULL,
  `safety_mode` varchar(16) NOT NULL,
  `source_version` bigint(20) UNSIGNED NOT NULL,
  `payload_json` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `matching_snapshot`
--

INSERT INTO `matching_snapshot` (`id`, `id_user`, `id_pref`, `price_mode`, `sort_by`, `safety_mode`, `source_version`, `payload_json`, `created_at`, `updated_at`) VALUES
(1, 1, 28, 'all', 'score', 'strict', 26, '{\"preference\":{\"id_pref\":28,\"regime_alimentaire\":\"fruits, halal, vegetarien\",\"allergies\":\"chocolat\",\"localisation\":\"École Superieur Privée d\'Ingénierie et de Technologie (ESPRIT), Rue de Newton, Zone Industrielle Chotrana II, Nkhilet, Délégation Raoued, Gouvernorat Ariana, 2088, Tunisie\",\"date_demande\":\"2026-05-05 15:59:23.000000\",\"id_user\":1},\"restaurants\":[{\"id_restaurant\":4,\"id_owner\":1,\"nom\":\"dar louay\",\"localisation\":\"Office National Des Huiles, Rue du 13 Aout, Ezzouhour, Bach-Hamba, Délégation Ezzouhour, Tunis, Gouvernorat Tunis, 2052, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260428_122516_0ec9a21a.jpg\",\"description\":\"ENA NOSKON FI BARDO BAHDHA L KLEB NBI3 CHAWARMA HOT DOG\",\"telephone\":\"+21698910180\",\"horaires\":\"11:00-20:00\",\"location_match\":false,\"distance_km\":6.84,\"matched_meals_count\":3,\"safe_meals_count\":3,\"allergy_conflict_meals\":0,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":0,\"regime_warning_meals\":0,\"unknown_meals_count\":0,\"has_free_options\":true,\"has_paid_options\":true,\"matched_meals\":[{\"meal_id\":\"meal_20260428122653_9dd0f1\",\"meal_name\":\"chawarma\",\"ingredients\":\"viande dinde, tomate, salade grillée, oignons, harissa, choux, pain libanais\",\"quantity\":7,\"pricing_mode\":\"paid\",\"price\":50,\"regime_tags\":\"bio, halal\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"bio\",\"halal\"],\"semantic_allergens\":[\"gluten\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"gluten\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"gluten\":[\"pain libanais\"]},\"ai_allergen_source_scopes\":{\"gluten\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"gluten\",\"trigger_ingredients\":[\"pain libanais\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false},{\"meal_id\":\"meal_20260505152344_adb8c9\",\"meal_name\":\"plat tunisien\",\"ingredients\":\"salade fraiche, salade grillée, thon, oeuf, harissa, mayonnaise\",\"quantity\":10,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"halal\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"halal\"],\"semantic_allergens\":[\"poisson\",\"oeuf\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"poisson\",\"oeuf\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"poisson\":[\"thon\"],\"oeuf\":[\"oeuf\",\"mayonnaise\"]},\"ai_allergen_source_scopes\":{\"poisson\":[\"vendor_ingredients\"],\"oeuf\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"poisson\",\"trigger_ingredients\":[\"thon\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"},{\"allergen\":\"oeuf\",\"trigger_ingredients\":[\"oeuf\",\"mayonnaise\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false},{\"meal_id\":\"meal_20260507104253_f8881b\",\"meal_name\":\"kafteji\",\"ingredients\":\"patate, oeufs, poivron vert, huile vegetale\",\"quantity\":6,\"pricing_mode\":\"paid\",\"price\":4,\"regime_tags\":\"halal, vegetarien\",\"allergens\":\"\",\"regime_overlap\":2,\"semantic_regimes\":[\"halal\",\"vegetarien\"],\"semantic_allergens\":[],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":[],\"ai_allergen_source_scopes\":[],\"ai_causal_allergen_details\":[],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false}],\"score\":80,\"matching_brief\":{\"engine\":\"caremeal-hybrid-ai-gemini\",\"safety_mode\":\"strict\",\"user_allergies_raw\":\"chocolat\",\"user_allergies_semantic\":[],\"user_allergy_keywords\":[\"chocolat\"],\"user_allergy_input_map\":{\"chocolat\":[\"lactose\",\"soja\"]},\"user_input_mapping\":[{\"raw_term\":\"chocolat\",\"exact_keyword\":\"chocolat\",\"mapped_allergens\":[],\"mapped_evidence\":[],\"mapped_all_tokens\":[]}],\"user_regimes_semantic\":[\"halal\",\"vegetarien\"],\"score_reasons\":[\"+70 regime compatibility\",\"+6 safe meals count\",\"+4 semantic coverage\"],\"analysis_mode\":\"ai_live\"}},{\"id_restaurant\":5,\"id_owner\":1,\"nom\":\"chez hamido\",\"localisation\":\"Lycée Khaznadar, Avenue de l\'Indépendance, Khaznadar, Délégation Le Bardo, Tunis, Gouvernorat Tunis, 2017, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260428_151324_2540233c.png\",\"description\":\"JE SUIS UN RESTOTOTOTOTOTOTOTTOTOTOTOTO\",\"telephone\":\"+21698437272\",\"horaires\":\"09:08-15:00\",\"location_match\":false,\"distance_km\":6.72,\"matched_meals_count\":2,\"safe_meals_count\":2,\"allergy_conflict_meals\":0,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":0,\"regime_warning_meals\":0,\"unknown_meals_count\":0,\"has_free_options\":false,\"has_paid_options\":true,\"matched_meals\":[{\"meal_id\":\"meal_20260503202442_c3496a\",\"meal_name\":\"cheese burger\",\"ingredients\":\"viande, laitue, tomate, ketchup, mayonnaise, fromage cheddar\",\"quantity\":15,\"pricing_mode\":\"paid\",\"price\":3,\"regime_tags\":\"equilibre, halal\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"equilibre\",\"halal\"],\"semantic_allergens\":[\"lactose\",\"oeuf\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"lactose\",\"oeuf\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"lactose\":[\"cheese burger\",\"fromage cheddar\"],\"oeuf\":[\"mayonnaise\"]},\"ai_allergen_source_scopes\":{\"lactose\":[\"vendor_ingredients\"],\"oeuf\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"lactose\",\"trigger_ingredients\":[\"cheese burger\",\"fromage cheddar\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"},{\"allergen\":\"oeuf\",\"trigger_ingredients\":[\"mayonnaise\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false},{\"meal_id\":\"meal_20260503202841_130fc7\",\"meal_name\":\"croque madame jambon\",\"ingredients\":\"pain de mie, oeuf, jambon, fromage mozza, mayonnaise\",\"quantity\":12,\"pricing_mode\":\"paid\",\"price\":2,\"regime_tags\":\"halal, budget\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"halal\",\"budget\"],\"semantic_allergens\":[\"gluten\",\"lactose\",\"oeuf\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"gluten\",\"lactose\",\"oeuf\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"gluten\":[\"pain de mie\"],\"oeuf\":[\"oeuf\",\"mayonnaise\"],\"lactose\":[\"fromage mozza\"]},\"ai_allergen_source_scopes\":{\"gluten\":[\"vendor_ingredients\"],\"oeuf\":[\"vendor_ingredients\"],\"lactose\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"gluten\",\"trigger_ingredients\":[\"pain de mie\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"},{\"allergen\":\"lactose\",\"trigger_ingredients\":[\"fromage mozza\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"},{\"allergen\":\"oeuf\",\"trigger_ingredients\":[\"oeuf\",\"mayonnaise\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false}],\"score\":46,\"matching_brief\":{\"engine\":\"caremeal-hybrid-ai-gemini\",\"safety_mode\":\"strict\",\"user_allergies_raw\":\"chocolat\",\"user_allergies_semantic\":[],\"user_allergy_keywords\":[\"chocolat\"],\"user_allergy_input_map\":{\"chocolat\":[\"lactose\",\"soja\"]},\"user_input_mapping\":[{\"raw_term\":\"chocolat\",\"exact_keyword\":\"chocolat\",\"mapped_allergens\":[],\"mapped_evidence\":[],\"mapped_all_tokens\":[]}],\"user_regimes_semantic\":[\"halal\",\"vegetarien\"],\"score_reasons\":[\"+38 regime compatibility\",\"+4 safe meals count\",\"+4 semantic coverage\"],\"analysis_mode\":\"ai_live\"}},{\"id_restaurant\":1,\"id_owner\":1,\"nom\":\"elvilla\",\"localisation\":\"El Menzah 5, Délégation Ariana Ville, Gouvernorat Ariana, 2091, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260420_110902_99e7f991.png\",\"description\":\"hahahahahaahahahahah\",\"telephone\":\"+21655552500\",\"horaires\":\"09:00-21:00\",\"location_match\":false,\"distance_km\":1.96,\"matched_meals_count\":1,\"safe_meals_count\":1,\"allergy_conflict_meals\":0,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":0,\"regime_warning_meals\":0,\"unknown_meals_count\":0,\"has_free_options\":true,\"has_paid_options\":false,\"matched_meals\":[{\"meal_id\":\"meal_20260420113202_b9ee4f\",\"meal_name\":\"pad thai\",\"ingredients\":\"cacahuete, nouille, sauce, carotte, champignon, poulet\",\"quantity\":8,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"bio, halal\",\"allergens\":\"cacahuete\",\"regime_overlap\":1,\"semantic_regimes\":[\"bio\",\"halal\"],\"semantic_allergens\":[\"arachide\"],\"declared_allergens_semantic\":[\"arachide\"],\"inferred_allergens_semantic\":[],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"declared\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"declared\",\"ai_confidence\":1,\"ai_explanation\":\"Allergenes declares par le vendeur. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":false,\"ai_legal_warning\":\"\",\"ai_allergen_sources\":{\"arachide\":[\"cacahuete\"]},\"ai_allergen_source_scopes\":{\"arachide\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"arachide\",\"trigger_ingredients\":[\"cacahuete\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":1,\"data_quality_confidence\":1,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":100,\"ai_data_quality_confidence\":1,\"ai_allergen_inference_confidence\":1,\"ai_check_passed\":true,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":1,\"inference_confidence\":1},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false}],\"score\":23,\"matching_brief\":{\"engine\":\"caremeal-hybrid-ai-gemini\",\"safety_mode\":\"strict\",\"user_allergies_raw\":\"chocolat\",\"user_allergies_semantic\":[],\"user_allergy_keywords\":[\"chocolat\"],\"user_allergy_input_map\":{\"chocolat\":[\"lactose\",\"soja\"]},\"user_input_mapping\":[{\"raw_term\":\"chocolat\",\"exact_keyword\":\"chocolat\",\"mapped_allergens\":[],\"mapped_evidence\":[],\"mapped_all_tokens\":[]}],\"user_regimes_semantic\":[\"halal\",\"vegetarien\"],\"score_reasons\":[\"+19 regime compatibility\",\"+2 safe meals count\",\"+2 semantic coverage\"],\"analysis_mode\":\"ai_live\"}},{\"id_restaurant\":6,\"id_owner\":1,\"nom\":\"sugar overload\",\"localisation\":\"La Marsa, Tunis, Gouvernorat Tunis, 2070, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260505_152835_f58fe7bd.jpg\",\"description\":\"BIENVENUE ICI TESTSTSTTSTSTSTSTSTSTTSTSTSTSSTST\",\"telephone\":\"+21638902918\",\"horaires\":\"10:00-22:00\",\"location_match\":false,\"distance_km\":6.84,\"matched_meals_count\":1,\"safe_meals_count\":1,\"allergy_conflict_meals\":1,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":0,\"regime_warning_meals\":0,\"unknown_meals_count\":0,\"has_free_options\":true,\"has_paid_options\":false,\"matched_meals\":[{\"meal_id\":\"meal_20260507110758_650d14\",\"meal_name\":\"kounefa\",\"ingredients\":\"fromage\",\"quantity\":10,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"vegetarien\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"vegetarien\"],\"semantic_allergens\":[\"lactose\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"lactose\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"lactose\":[\"fromage\"]},\"ai_allergen_source_scopes\":{\"lactose\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"lactose\",\"trigger_ingredients\":[\"fromage\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false}],\"score\":18,\"matching_brief\":{\"engine\":\"caremeal-hybrid-ai-gemini\",\"safety_mode\":\"strict\",\"user_allergies_raw\":\"chocolat\",\"user_allergies_semantic\":[],\"user_allergy_keywords\":[\"chocolat\"],\"user_allergy_input_map\":{\"chocolat\":[\"lactose\",\"soja\"]},\"user_input_mapping\":[{\"raw_term\":\"chocolat\",\"exact_keyword\":\"chocolat\",\"mapped_allergens\":[],\"mapped_evidence\":[],\"mapped_all_tokens\":[]}],\"user_regimes_semantic\":[\"halal\",\"vegetarien\"],\"score_reasons\":[\"+19 regime compatibility\",\"+2 safe meals count\",\"-5 allergy conflicts excluded\",\"+2 semantic coverage\"],\"analysis_mode\":\"ai_live\"}}],\"applied_filters\":{\"price_mode\":\"all\",\"sort_by\":\"score\",\"safety_mode\":\"strict\",\"semantic_engine\":\"caremeal-hybrid-ai-gemini\"}}', '2026-05-07 09:02:42', '2026-05-07 10:38:36'),
(3, 1, 28, 'all', 'score', 'souple', 26, '{\"preference\":{\"id_pref\":28,\"regime_alimentaire\":\"fruits, halal, vegetarien\",\"allergies\":\"chocolat\",\"localisation\":\"École Superieur Privée d\'Ingénierie et de Technologie (ESPRIT), Rue de Newton, Zone Industrielle Chotrana II, Nkhilet, Délégation Raoued, Gouvernorat Ariana, 2088, Tunisie\",\"date_demande\":\"2026-05-05 15:59:23.000000\",\"id_user\":1},\"restaurants\":[{\"id_restaurant\":4,\"id_owner\":1,\"nom\":\"dar louay\",\"localisation\":\"Office National Des Huiles, Rue du 13 Aout, Ezzouhour, Bach-Hamba, Délégation Ezzouhour, Tunis, Gouvernorat Tunis, 2052, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260428_122516_0ec9a21a.jpg\",\"description\":\"ENA NOSKON FI BARDO BAHDHA L KLEB NBI3 CHAWARMA HOT DOG\",\"telephone\":\"+21698910180\",\"horaires\":\"11:00-20:00\",\"location_match\":false,\"distance_km\":6.84,\"matched_meals_count\":3,\"safe_meals_count\":3,\"allergy_conflict_meals\":0,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":0,\"regime_warning_meals\":0,\"unknown_meals_count\":0,\"has_free_options\":true,\"has_paid_options\":true,\"matched_meals\":[{\"meal_id\":\"meal_20260428122653_9dd0f1\",\"meal_name\":\"chawarma\",\"ingredients\":\"viande dinde, tomate, salade grillée, oignons, harissa, choux, pain libanais\",\"quantity\":6,\"pricing_mode\":\"paid\",\"price\":50,\"regime_tags\":\"bio, halal\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"bio\",\"halal\"],\"semantic_allergens\":[\"gluten\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"gluten\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"gluten\":[\"pain libanais\"]},\"ai_allergen_source_scopes\":{\"gluten\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"gluten\",\"trigger_ingredients\":[\"pain libanais\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false},{\"meal_id\":\"meal_20260505152344_adb8c9\",\"meal_name\":\"plat tunisien\",\"ingredients\":\"salade fraiche, salade grillée, thon, oeuf, harissa, mayonnaise\",\"quantity\":10,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"halal\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"halal\"],\"semantic_allergens\":[\"poisson\",\"oeuf\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"poisson\",\"oeuf\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"poisson\":[\"thon\"],\"oeuf\":[\"oeuf\",\"mayonnaise\"]},\"ai_allergen_source_scopes\":{\"poisson\":[\"vendor_ingredients\"],\"oeuf\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"poisson\",\"trigger_ingredients\":[\"thon\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"},{\"allergen\":\"oeuf\",\"trigger_ingredients\":[\"oeuf\",\"mayonnaise\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false},{\"meal_id\":\"meal_20260507104253_f8881b\",\"meal_name\":\"kafteji\",\"ingredients\":\"patate, oeufs, poivron vert, huile vegetale\",\"quantity\":6,\"pricing_mode\":\"paid\",\"price\":4,\"regime_tags\":\"halal, vegetarien\",\"allergens\":\"\",\"regime_overlap\":2,\"semantic_regimes\":[\"halal\",\"vegetarien\"],\"semantic_allergens\":[],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":[],\"ai_allergen_source_scopes\":[],\"ai_causal_allergen_details\":[],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false}],\"score\":80,\"matching_brief\":{\"engine\":\"caremeal-hybrid-ai-gemini\",\"safety_mode\":\"souple\",\"user_allergies_raw\":\"chocolat\",\"user_allergies_semantic\":[],\"user_allergy_keywords\":[\"chocolat\"],\"user_allergy_input_map\":{\"chocolat\":[\"soja\",\"lactose\"]},\"user_input_mapping\":[{\"raw_term\":\"chocolat\",\"exact_keyword\":\"chocolat\",\"mapped_allergens\":[],\"mapped_evidence\":[],\"mapped_all_tokens\":[]}],\"user_regimes_semantic\":[\"halal\",\"vegetarien\"],\"score_reasons\":[\"+70 regime compatibility\",\"+6 safe meals count\",\"+4 semantic coverage\"],\"analysis_mode\":\"ai_live\"}},{\"id_restaurant\":6,\"id_owner\":1,\"nom\":\"sugar overload\",\"localisation\":\"La Marsa, Tunis, Gouvernorat Tunis, 2070, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260505_152835_f58fe7bd.jpg\",\"description\":\"BIENVENUE ICI TESTSTSTTSTSTSTSTSTSTTSTSTSTSSTST\",\"telephone\":\"+21638902918\",\"horaires\":\"10:00-22:00\",\"location_match\":false,\"distance_km\":6.84,\"matched_meals_count\":3,\"safe_meals_count\":1,\"allergy_conflict_meals\":1,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":0,\"regime_warning_meals\":1,\"unknown_meals_count\":0,\"has_free_options\":true,\"has_paid_options\":false,\"matched_meals\":[{\"meal_id\":\"meal_20260507072209_922713\",\"meal_name\":\"tarte aux fruits\",\"ingredients\":\"fruits\",\"quantity\":7,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"fruits\",\"allergens\":\"\",\"regime_overlap\":0,\"semantic_regimes\":[\"fruits\"],\"semantic_allergens\":[],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":false,\"warning_regime\":true,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":true,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":[],\"ai_allergen_source_scopes\":[],\"ai_causal_allergen_details\":[],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false},{\"meal_id\":\"meal_20260507104422_870f35\",\"meal_name\":\"brownies\",\"ingredients\":\"chocolat\",\"quantity\":20,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"halal, vegetarien\",\"allergens\":\"\",\"regime_overlap\":2,\"semantic_regimes\":[\"halal\",\"vegetarien\"],\"semantic_allergens\":[],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":true,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":true,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":[],\"ai_allergen_source_scopes\":[],\"ai_causal_allergen_details\":[],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[{\"type\":\"keyword_exact\",\"raw_term\":\"chocolat\",\"allergen\":\"chocolat\",\"origin\":\"text_direct\",\"origin_label\":\"Correspondance preference\",\"trigger_ingredients\":[\"chocolat\"],\"source_scopes\":[\"text_direct\"],\"confidence\":1}],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[\"chocolat\"],\"trace_risk\":false},{\"meal_id\":\"meal_20260507110758_650d14\",\"meal_name\":\"kounefa\",\"ingredients\":\"fromage\",\"quantity\":10,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"vegetarien\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"vegetarien\"],\"semantic_allergens\":[\"lactose\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"lactose\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"lactose\":[\"fromage\"]},\"ai_allergen_source_scopes\":{\"lactose\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"lactose\",\"trigger_ingredients\":[\"fromage\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false}],\"score\":50,\"matching_brief\":{\"engine\":\"caremeal-hybrid-ai-gemini\",\"safety_mode\":\"souple\",\"user_allergies_raw\":\"chocolat\",\"user_allergies_semantic\":[],\"user_allergy_keywords\":[\"chocolat\"],\"user_allergy_input_map\":{\"chocolat\":[\"soja\",\"lactose\"]},\"user_input_mapping\":[{\"raw_term\":\"chocolat\",\"exact_keyword\":\"chocolat\",\"mapped_allergens\":[],\"mapped_evidence\":[],\"mapped_all_tokens\":[]}],\"user_regimes_semantic\":[\"halal\",\"vegetarien\"],\"score_reasons\":[\"+51 regime compatibility\",\"+2 safe meals count\",\"-5 allergy conflicts excluded\",\"+2 semantic coverage\",\"-4 meals hors regime (mode souple)\"],\"analysis_mode\":\"ai_live\"}},{\"id_restaurant\":5,\"id_owner\":1,\"nom\":\"chez hamido\",\"localisation\":\"Lycée Khaznadar, Avenue de l\'Indépendance, Khaznadar, Délégation Le Bardo, Tunis, Gouvernorat Tunis, 2017, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260428_151324_2540233c.png\",\"description\":\"JE SUIS UN RESTOTOTOTOTOTOTOTTOTOTOTOTO\",\"telephone\":\"+21698437272\",\"horaires\":\"09:08-15:00\",\"location_match\":false,\"distance_km\":6.72,\"matched_meals_count\":2,\"safe_meals_count\":2,\"allergy_conflict_meals\":0,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":0,\"regime_warning_meals\":0,\"unknown_meals_count\":0,\"has_free_options\":false,\"has_paid_options\":true,\"matched_meals\":[{\"meal_id\":\"meal_20260503202442_c3496a\",\"meal_name\":\"cheese burger\",\"ingredients\":\"viande, laitue, tomate, ketchup, mayonnaise, fromage cheddar\",\"quantity\":15,\"pricing_mode\":\"paid\",\"price\":3,\"regime_tags\":\"equilibre, halal\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"equilibre\",\"halal\"],\"semantic_allergens\":[\"lactose\",\"oeuf\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"lactose\",\"oeuf\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"lactose\":[\"cheese burger\",\"fromage cheddar\"],\"oeuf\":[\"mayonnaise\"]},\"ai_allergen_source_scopes\":{\"lactose\":[\"vendor_ingredients\"],\"oeuf\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"lactose\",\"trigger_ingredients\":[\"cheese burger\",\"fromage cheddar\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"},{\"allergen\":\"oeuf\",\"trigger_ingredients\":[\"mayonnaise\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false},{\"meal_id\":\"meal_20260503202841_130fc7\",\"meal_name\":\"croque madame jambon\",\"ingredients\":\"pain de mie, oeuf, jambon, fromage mozza, mayonnaise\",\"quantity\":12,\"pricing_mode\":\"paid\",\"price\":2,\"regime_tags\":\"halal, budget\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"halal\",\"budget\"],\"semantic_allergens\":[\"gluten\",\"lactose\",\"oeuf\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"gluten\",\"lactose\",\"oeuf\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"gluten\":[\"pain de mie\"],\"oeuf\":[\"oeuf\",\"mayonnaise\"],\"lactose\":[\"fromage mozza\"]},\"ai_allergen_source_scopes\":{\"gluten\":[\"vendor_ingredients\"],\"oeuf\":[\"vendor_ingredients\"],\"lactose\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"gluten\",\"trigger_ingredients\":[\"pain de mie\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"},{\"allergen\":\"lactose\",\"trigger_ingredients\":[\"fromage mozza\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"},{\"allergen\":\"oeuf\",\"trigger_ingredients\":[\"oeuf\",\"mayonnaise\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false}],\"score\":46,\"matching_brief\":{\"engine\":\"caremeal-hybrid-ai-gemini\",\"safety_mode\":\"souple\",\"user_allergies_raw\":\"chocolat\",\"user_allergies_semantic\":[],\"user_allergy_keywords\":[\"chocolat\"],\"user_allergy_input_map\":{\"chocolat\":[\"soja\",\"lactose\"]},\"user_input_mapping\":[{\"raw_term\":\"chocolat\",\"exact_keyword\":\"chocolat\",\"mapped_allergens\":[],\"mapped_evidence\":[],\"mapped_all_tokens\":[]}],\"user_regimes_semantic\":[\"halal\",\"vegetarien\"],\"score_reasons\":[\"+38 regime compatibility\",\"+4 safe meals count\",\"+4 semantic coverage\"],\"analysis_mode\":\"ai_live\"}},{\"id_restaurant\":1,\"id_owner\":1,\"nom\":\"elvilla\",\"localisation\":\"El Menzah 5, Délégation Ariana Ville, Gouvernorat Ariana, 2091, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260420_110902_99e7f991.png\",\"description\":\"hahahahahaahahahahah\",\"telephone\":\"+21655552500\",\"horaires\":\"09:00-21:00\",\"location_match\":false,\"distance_km\":1.96,\"matched_meals_count\":1,\"safe_meals_count\":1,\"allergy_conflict_meals\":0,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":0,\"regime_warning_meals\":0,\"unknown_meals_count\":0,\"has_free_options\":true,\"has_paid_options\":false,\"matched_meals\":[{\"meal_id\":\"meal_20260420113202_b9ee4f\",\"meal_name\":\"pad thai\",\"ingredients\":\"cacahuete, nouille, sauce, carotte, champignon, poulet\",\"quantity\":7,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"bio, halal\",\"allergens\":\"cacahuete\",\"regime_overlap\":1,\"semantic_regimes\":[\"bio\",\"halal\"],\"semantic_allergens\":[\"arachide\"],\"declared_allergens_semantic\":[\"arachide\"],\"inferred_allergens_semantic\":[],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"declared\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"declared\",\"ai_confidence\":1,\"ai_explanation\":\"Allergenes declares par le vendeur. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":false,\"ai_legal_warning\":\"\",\"ai_allergen_sources\":{\"arachide\":[\"cacahuete\"]},\"ai_allergen_source_scopes\":{\"arachide\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"arachide\",\"trigger_ingredients\":[\"cacahuete\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":1,\"data_quality_confidence\":1,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":100,\"ai_data_quality_confidence\":1,\"ai_allergen_inference_confidence\":1,\"ai_check_passed\":true,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":1,\"inference_confidence\":1},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false}],\"score\":23,\"matching_brief\":{\"engine\":\"caremeal-hybrid-ai-gemini\",\"safety_mode\":\"souple\",\"user_allergies_raw\":\"chocolat\",\"user_allergies_semantic\":[],\"user_allergy_keywords\":[\"chocolat\"],\"user_allergy_input_map\":{\"chocolat\":[\"soja\",\"lactose\"]},\"user_input_mapping\":[{\"raw_term\":\"chocolat\",\"exact_keyword\":\"chocolat\",\"mapped_allergens\":[],\"mapped_evidence\":[],\"mapped_all_tokens\":[]}],\"user_regimes_semantic\":[\"halal\",\"vegetarien\"],\"score_reasons\":[\"+19 regime compatibility\",\"+2 safe meals count\",\"+2 semantic coverage\"],\"analysis_mode\":\"ai_live\"}}],\"applied_filters\":{\"price_mode\":\"all\",\"sort_by\":\"score\",\"safety_mode\":\"souple\",\"semantic_engine\":\"caremeal-hybrid-ai-gemini\"}}', '2026-05-07 09:27:37', '2026-05-07 11:05:51');
INSERT INTO `matching_snapshot` (`id`, `id_user`, `id_pref`, `price_mode`, `sort_by`, `safety_mode`, `source_version`, `payload_json`, `created_at`, `updated_at`) VALUES
(4, 1, 27, 'all', 'score', 'strict', 12, '{\"preference\":{\"id_pref\":27,\"regime_alimentaire\":\"halal\",\"allergies\":\"cheesecake\",\"localisation\":\"Rue Pasteur, Ennasr 1, Nasr 1, Délégation Ariana Ville, Gouvernorat Ariana, 2037, Tunisie\",\"date_demande\":\"2026-05-05 08:57:38.000000\",\"id_user\":1},\"restaurants\":[{\"id_restaurant\":4,\"id_owner\":1,\"nom\":\"dar louay\",\"localisation\":\"Office National Des Huiles, Rue du 13 Aout, Ezzouhour, Bach-Hamba, Délégation Ezzouhour, Tunis, Gouvernorat Tunis, 2052, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260428_122516_0ec9a21a.jpg\",\"description\":\"ENA NOSKON FI BARDO BAHDHA L KLEB NBI3 CHAWARMA HOT DOG\",\"telephone\":\"+21698910180\",\"horaires\":\"11:00-20:00\",\"location_match\":false,\"distance_km\":6.84,\"matched_meals_count\":2,\"safe_meals_count\":2,\"allergy_conflict_meals\":0,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":0,\"has_free_options\":true,\"has_paid_options\":true,\"matched_meals\":[{\"meal_id\":\"meal_20260428122653_9dd0f1\",\"meal_name\":\"chawarma\",\"ingredients\":\"viande dinde, tomate, salade grillée, oignons, harissa, choux, pain libanais\",\"quantity\":7,\"pricing_mode\":\"paid\",\"price\":50,\"regime_tags\":\"bio, halal\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"bio\",\"halal\"],\"semantic_allergens\":[\"gluten\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"gluten\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"gluten\":[\"pain libanais\"]},\"ai_allergen_source_scopes\":{\"gluten\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"gluten\",\"trigger_ingredients\":[\"pain libanais\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false},{\"meal_id\":\"meal_20260505152344_adb8c9\",\"meal_name\":\"plat tunisien\",\"ingredients\":\"salade fraiche, salade grillée, thon, oeuf, harissa, mayonnaise\",\"quantity\":10,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"halal\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"halal\"],\"semantic_allergens\":[\"poisson\",\"oeuf\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"poisson\",\"oeuf\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"poisson\":[\"thon\"],\"oeuf\":[\"oeuf\",\"mayonnaise\"]},\"ai_allergen_source_scopes\":{\"poisson\":[\"vendor_ingredients\"],\"oeuf\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"poisson\",\"trigger_ingredients\":[\"thon\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"},{\"allergen\":\"oeuf\",\"trigger_ingredients\":[\"oeuf\",\"mayonnaise\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false}],\"score\":46,\"matching_brief\":{\"engine\":\"caremeal-hybrid-ai-gemini\",\"safety_mode\":\"strict\",\"user_allergies_raw\":\"cheesecake\",\"user_allergies_semantic\":[],\"user_allergy_keywords\":[\"cheesecake\"],\"user_allergy_input_map\":{\"cheesecake\":[\"lactose\",\"oeuf\",\"gluten\"]},\"user_input_mapping\":[{\"raw_term\":\"cheesecake\",\"exact_keyword\":\"cheesecake\",\"mapped_allergens\":[],\"mapped_evidence\":[],\"mapped_all_tokens\":[]}],\"user_regimes_semantic\":[\"halal\"],\"score_reasons\":[\"+38 regime compatibility\",\"+4 safe meals count\",\"+4 semantic coverage\"],\"analysis_mode\":\"ai_live\"}},{\"id_restaurant\":1,\"id_owner\":1,\"nom\":\"elvilla\",\"localisation\":\"El Menzah 5, Délégation Ariana Ville, Gouvernorat Ariana, 2091, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260420_110902_99e7f991.png\",\"description\":\"hahahahahaahahahahah\",\"telephone\":\"+21655552500\",\"horaires\":\"09:00-21:00\",\"location_match\":false,\"distance_km\":1.96,\"matched_meals_count\":1,\"safe_meals_count\":1,\"allergy_conflict_meals\":0,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":0,\"has_free_options\":true,\"has_paid_options\":false,\"matched_meals\":[{\"meal_id\":\"meal_20260420113202_b9ee4f\",\"meal_name\":\"dddd\",\"ingredients\":\"agagagagaagagagaga\",\"quantity\":5,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"bio, halal\",\"allergens\":\"cacahuete\",\"regime_overlap\":1,\"semantic_regimes\":[\"bio\",\"halal\"],\"semantic_allergens\":[\"arachide\"],\"declared_allergens_semantic\":[\"arachide\"],\"inferred_allergens_semantic\":[],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"declared\",\"ai_source\":\"declared\",\"ai_confidence\":1,\"ai_explanation\":\"Allergenes declares par le vendeur. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":false,\"ai_legal_warning\":\"\",\"ai_allergen_sources\":{\"arachide\":[\"allergene declare par le vendeur\"]},\"ai_allergen_source_scopes\":{\"arachide\":[\"vendor_declared\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"arachide\",\"trigger_ingredients\":[\"allergene declare par le vendeur\"],\"source_scopes\":[\"vendor_declared\"],\"confidence\":1,\"data_quality_confidence\":1,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":100,\"ai_data_quality_confidence\":1,\"ai_allergen_inference_confidence\":1,\"ai_check_passed\":true,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":1,\"inference_confidence\":1},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false}],\"score\":23,\"matching_brief\":{\"engine\":\"caremeal-hybrid-ai-gemini\",\"safety_mode\":\"strict\",\"user_allergies_raw\":\"cheesecake\",\"user_allergies_semantic\":[],\"user_allergy_keywords\":[\"cheesecake\"],\"user_allergy_input_map\":{\"cheesecake\":[\"lactose\",\"oeuf\",\"gluten\"]},\"user_input_mapping\":[{\"raw_term\":\"cheesecake\",\"exact_keyword\":\"cheesecake\",\"mapped_allergens\":[],\"mapped_evidence\":[],\"mapped_all_tokens\":[]}],\"user_regimes_semantic\":[\"halal\"],\"score_reasons\":[\"+19 regime compatibility\",\"+2 safe meals count\",\"+2 semantic coverage\"],\"analysis_mode\":\"ai_live\"}},{\"id_restaurant\":5,\"id_owner\":1,\"nom\":\"chez hamido\",\"localisation\":\"Lycée Khaznadar, Avenue de l\'Indépendance, Khaznadar, Délégation Le Bardo, Tunis, Gouvernorat Tunis, 2017, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260428_151324_2540233c.png\",\"description\":\"JE SUIS UN RESTOTOTOTOTOTOTOTTOTOTOTOTO\",\"telephone\":\"+21698437272\",\"horaires\":\"09:08-15:00\",\"location_match\":false,\"distance_km\":6.72,\"matched_meals_count\":1,\"safe_meals_count\":1,\"allergy_conflict_meals\":0,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":0,\"has_free_options\":false,\"has_paid_options\":true,\"matched_meals\":[{\"meal_id\":\"meal_20260503202442_c3496a\",\"meal_name\":\"cheese burger\",\"ingredients\":\"viande, laitue, tomate, ketchup, mayonnaise, fromage cheddar\",\"quantity\":15,\"pricing_mode\":\"paid\",\"price\":3,\"regime_tags\":\"equilibre, halal\",\"allergens\":\"aucun\",\"regime_overlap\":1,\"semantic_regimes\":[\"equilibre\",\"halal\"],\"semantic_allergens\":[\"lactose\",\"oeuf\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"lactose\",\"oeuf\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"lactose\":[\"cheese burger\",\"fromage cheddar\"],\"oeuf\":[\"mayonnaise\"]},\"ai_allergen_source_scopes\":{\"lactose\":[\"vendor_ingredients\"],\"oeuf\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"lactose\",\"trigger_ingredients\":[\"cheese burger\",\"fromage cheddar\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"},{\"allergen\":\"oeuf\",\"trigger_ingredients\":[\"mayonnaise\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false}],\"score\":23,\"matching_brief\":{\"engine\":\"caremeal-hybrid-ai-gemini\",\"safety_mode\":\"strict\",\"user_allergies_raw\":\"cheesecake\",\"user_allergies_semantic\":[],\"user_allergy_keywords\":[\"cheesecake\"],\"user_allergy_input_map\":{\"cheesecake\":[\"lactose\",\"oeuf\",\"gluten\"]},\"user_input_mapping\":[{\"raw_term\":\"cheesecake\",\"exact_keyword\":\"cheesecake\",\"mapped_allergens\":[],\"mapped_evidence\":[],\"mapped_all_tokens\":[]}],\"user_regimes_semantic\":[\"halal\"],\"score_reasons\":[\"+19 regime compatibility\",\"+2 safe meals count\",\"+2 semantic coverage\"],\"analysis_mode\":\"ai_live\"}}],\"applied_filters\":{\"price_mode\":\"all\",\"sort_by\":\"score\",\"safety_mode\":\"strict\",\"semantic_engine\":\"caremeal-hybrid-ai-gemini\"}}', '2026-05-07 09:30:34', '2026-05-07 09:30:34'),
(12, 1, 29, 'all', 'score', 'strict', 27, '{\"preference\":{\"id_pref\":29,\"regime_alimentaire\":\"halal, vegetarien\",\"allergies\":\"chocolat au lait\",\"localisation\":\"Rue Pasteur, Ennasr 1, Nasr 1, Délégation Ariana Ville, Gouvernorat Ariana, 2037, Tunisie\",\"date_demande\":\"2026-05-07 12:07:16.000000\",\"id_user\":1},\"restaurants\":[{\"id_restaurant\":4,\"id_owner\":1,\"nom\":\"dar louay\",\"localisation\":\"Office National Des Huiles, Rue du 13 Aout, Ezzouhour, Bach-Hamba, Délégation Ezzouhour, Tunis, Gouvernorat Tunis, 2052, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260428_122516_0ec9a21a.jpg\",\"description\":\"ENA NOSKON FI BARDO BAHDHA L KLEB NBI3 CHAWARMA HOT DOG\",\"telephone\":\"+21698910180\",\"horaires\":\"11:00-20:00\",\"location_match\":false,\"distance_km\":6.84,\"matched_meals_count\":3,\"safe_meals_count\":3,\"allergy_conflict_meals\":0,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":0,\"regime_warning_meals\":0,\"unknown_meals_count\":0,\"has_free_options\":true,\"has_paid_options\":true,\"matched_meals\":[{\"meal_id\":\"meal_20260428122653_9dd0f1\",\"meal_name\":\"chawarma\",\"ingredients\":\"viande dinde, tomate, salade grillée, oignons, harissa, choux, pain libanais\",\"quantity\":6,\"pricing_mode\":\"paid\",\"price\":50,\"regime_tags\":\"bio, halal\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"bio\",\"halal\"],\"semantic_allergens\":[\"gluten\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"gluten\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"gluten\":[\"pain libanais\"]},\"ai_allergen_source_scopes\":{\"gluten\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"gluten\",\"trigger_ingredients\":[\"pain libanais\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false},{\"meal_id\":\"meal_20260505152344_adb8c9\",\"meal_name\":\"plat tunisien\",\"ingredients\":\"salade fraiche, salade grillée, thon, oeuf, harissa, mayonnaise\",\"quantity\":10,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"halal\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"halal\"],\"semantic_allergens\":[\"poisson\",\"oeuf\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"poisson\",\"oeuf\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"poisson\":[\"thon\"],\"oeuf\":[\"oeuf\",\"mayonnaise\"]},\"ai_allergen_source_scopes\":{\"poisson\":[\"vendor_ingredients\"],\"oeuf\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"poisson\",\"trigger_ingredients\":[\"thon\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"},{\"allergen\":\"oeuf\",\"trigger_ingredients\":[\"oeuf\",\"mayonnaise\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false},{\"meal_id\":\"meal_20260507104253_f8881b\",\"meal_name\":\"kafteji\",\"ingredients\":\"patate, oeufs, poivron vert, huile vegetale\",\"quantity\":6,\"pricing_mode\":\"paid\",\"price\":4,\"regime_tags\":\"halal, vegetarien\",\"allergens\":\"\",\"regime_overlap\":2,\"semantic_regimes\":[\"halal\",\"vegetarien\"],\"semantic_allergens\":[],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":[],\"ai_allergen_source_scopes\":[],\"ai_causal_allergen_details\":[],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false}],\"score\":80,\"matching_brief\":{\"engine\":\"caremeal-hybrid-ai-gemini\",\"safety_mode\":\"strict\",\"user_allergies_raw\":\"chocolat au lait\",\"user_allergies_semantic\":[\"lactose\"],\"user_allergy_keywords\":[\"chocolat-au-lait\",\"chocolat\",\"chocolat-lait\",\"lait\"],\"user_allergy_input_map\":{\"chocolat au lait\":[\"lactose\",\"soja\"]},\"user_input_mapping\":[{\"raw_term\":\"chocolat au lait\",\"exact_keyword\":\"chocolat-au-lait\",\"mapped_allergens\":[\"lactose\"],\"mapped_evidence\":[],\"mapped_all_tokens\":[\"lactose\"]}],\"user_regimes_semantic\":[\"halal\",\"vegetarien\"],\"score_reasons\":[\"+70 regime compatibility\",\"+6 safe meals count\",\"+4 semantic coverage\"],\"analysis_mode\":\"ai_live\"}},{\"id_restaurant\":6,\"id_owner\":1,\"nom\":\"sugar overload\",\"localisation\":\"La Marsa, Tunis, Gouvernorat Tunis, 2070, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260505_152835_f58fe7bd.jpg\",\"description\":\"BIENVENUE ICI TESTSTSTTSTSTSTSTSTSTTSTSTSTSSTST\",\"telephone\":\"+21638902918\",\"horaires\":\"10:00-22:00\",\"location_match\":false,\"distance_km\":6.84,\"matched_meals_count\":2,\"safe_meals_count\":2,\"allergy_conflict_meals\":0,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":1,\"regime_warning_meals\":0,\"unknown_meals_count\":0,\"has_free_options\":true,\"has_paid_options\":false,\"matched_meals\":[{\"meal_id\":\"meal_20260507104422_870f35\",\"meal_name\":\"brownies\",\"ingredients\":\"chocolat\",\"quantity\":20,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"halal, vegetarien\",\"allergens\":\"\",\"regime_overlap\":2,\"semantic_regimes\":[\"halal\",\"vegetarien\"],\"semantic_allergens\":[],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":[],\"ai_allergen_source_scopes\":[],\"ai_causal_allergen_details\":[],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false},{\"meal_id\":\"meal_20260507110758_650d14\",\"meal_name\":\"kounefa\",\"ingredients\":\"fromage\",\"quantity\":10,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"vegetarien\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"vegetarien\"],\"semantic_allergens\":[\"lactose\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"lactose\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":true,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":true,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"lactose\":[\"fromage\"]},\"ai_allergen_source_scopes\":{\"lactose\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"lactose\",\"trigger_ingredients\":[\"fromage\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[{\"type\":\"ai_inferred\",\"raw_term\":\"chocolat au lait\",\"allergen\":\"lactose\",\"origin\":\"ai_precomputed\",\"origin_label\":\"Analyse IA pre-calculee\",\"trigger_ingredients\":[\"fromage\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55}],\"ai_potential_conflict\":true,\"ai_conflict_tokens\":[\"lactose\"],\"trace_risk\":false}],\"score\":60,\"matching_brief\":{\"engine\":\"caremeal-hybrid-ai-gemini\",\"safety_mode\":\"strict\",\"user_allergies_raw\":\"chocolat au lait\",\"user_allergies_semantic\":[\"lactose\"],\"user_allergy_keywords\":[\"chocolat-au-lait\",\"chocolat\",\"chocolat-lait\",\"lait\"],\"user_allergy_input_map\":{\"chocolat au lait\":[\"lactose\",\"soja\"]},\"user_input_mapping\":[{\"raw_term\":\"chocolat au lait\",\"exact_keyword\":\"chocolat-au-lait\",\"mapped_allergens\":[\"lactose\"],\"mapped_evidence\":[],\"mapped_all_tokens\":[\"lactose\"]}],\"user_regimes_semantic\":[\"halal\",\"vegetarien\"],\"score_reasons\":[\"+54 regime compatibility\",\"+4 safe meals count\",\"+2 semantic coverage\"],\"analysis_mode\":\"ai_live\"}},{\"id_restaurant\":5,\"id_owner\":1,\"nom\":\"chez hamido\",\"localisation\":\"Lycée Khaznadar, Avenue de l\'Indépendance, Khaznadar, Délégation Le Bardo, Tunis, Gouvernorat Tunis, 2017, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260428_151324_2540233c.png\",\"description\":\"JE SUIS UN RESTOTOTOTOTOTOTOTTOTOTOTOTO\",\"telephone\":\"+21698437272\",\"horaires\":\"09:08-15:00\",\"location_match\":false,\"distance_km\":6.72,\"matched_meals_count\":2,\"safe_meals_count\":2,\"allergy_conflict_meals\":0,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":2,\"regime_warning_meals\":0,\"unknown_meals_count\":0,\"has_free_options\":false,\"has_paid_options\":true,\"matched_meals\":[{\"meal_id\":\"meal_20260503202442_c3496a\",\"meal_name\":\"cheese burger\",\"ingredients\":\"viande, laitue, tomate, ketchup, mayonnaise, fromage cheddar\",\"quantity\":15,\"pricing_mode\":\"paid\",\"price\":3,\"regime_tags\":\"equilibre, halal\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"equilibre\",\"halal\"],\"semantic_allergens\":[\"lactose\",\"oeuf\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"lactose\",\"oeuf\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":true,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":true,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"lactose\":[\"cheese burger\",\"fromage cheddar\"],\"oeuf\":[\"mayonnaise\"]},\"ai_allergen_source_scopes\":{\"lactose\":[\"vendor_ingredients\"],\"oeuf\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"lactose\",\"trigger_ingredients\":[\"cheese burger\",\"fromage cheddar\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"},{\"allergen\":\"oeuf\",\"trigger_ingredients\":[\"mayonnaise\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[{\"type\":\"ai_inferred\",\"raw_term\":\"chocolat au lait\",\"allergen\":\"lactose\",\"origin\":\"ai_precomputed\",\"origin_label\":\"Analyse IA pre-calculee\",\"trigger_ingredients\":[\"cheese burger\",\"fromage cheddar\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55}],\"ai_potential_conflict\":true,\"ai_conflict_tokens\":[\"lactose\"],\"trace_risk\":false},{\"meal_id\":\"meal_20260503202841_130fc7\",\"meal_name\":\"croque madame jambon\",\"ingredients\":\"pain de mie, oeuf, jambon, fromage mozza, mayonnaise\",\"quantity\":12,\"pricing_mode\":\"paid\",\"price\":2,\"regime_tags\":\"halal, budget\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"halal\",\"budget\"],\"semantic_allergens\":[\"gluten\",\"lactose\",\"oeuf\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"gluten\",\"lactose\",\"oeuf\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":true,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":true,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"gluten\":[\"pain de mie\"],\"oeuf\":[\"oeuf\",\"mayonnaise\"],\"lactose\":[\"fromage mozza\"]},\"ai_allergen_source_scopes\":{\"gluten\":[\"vendor_ingredients\"],\"oeuf\":[\"vendor_ingredients\"],\"lactose\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"gluten\",\"trigger_ingredients\":[\"pain de mie\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"},{\"allergen\":\"lactose\",\"trigger_ingredients\":[\"fromage mozza\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"},{\"allergen\":\"oeuf\",\"trigger_ingredients\":[\"oeuf\",\"mayonnaise\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[{\"type\":\"ai_inferred\",\"raw_term\":\"chocolat au lait\",\"allergen\":\"lactose\",\"origin\":\"ai_precomputed\",\"origin_label\":\"Analyse IA pre-calculee\",\"trigger_ingredients\":[\"fromage mozza\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55}],\"ai_potential_conflict\":true,\"ai_conflict_tokens\":[\"lactose\"],\"trace_risk\":false}],\"score\":46,\"matching_brief\":{\"engine\":\"caremeal-hybrid-ai-gemini\",\"safety_mode\":\"strict\",\"user_allergies_raw\":\"chocolat au lait\",\"user_allergies_semantic\":[\"lactose\"],\"user_allergy_keywords\":[\"chocolat-au-lait\",\"chocolat\",\"chocolat-lait\",\"lait\"],\"user_allergy_input_map\":{\"chocolat au lait\":[\"lactose\",\"soja\"]},\"user_input_mapping\":[{\"raw_term\":\"chocolat au lait\",\"exact_keyword\":\"chocolat-au-lait\",\"mapped_allergens\":[\"lactose\"],\"mapped_evidence\":[],\"mapped_all_tokens\":[\"lactose\"]}],\"user_regimes_semantic\":[\"halal\",\"vegetarien\"],\"score_reasons\":[\"+38 regime compatibility\",\"+4 safe meals count\",\"+4 semantic coverage\"],\"analysis_mode\":\"ai_live\"}},{\"id_restaurant\":1,\"id_owner\":1,\"nom\":\"elvilla\",\"localisation\":\"El Menzah 5, Délégation Ariana Ville, Gouvernorat Ariana, 2091, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260420_110902_99e7f991.png\",\"description\":\"hahahahahaahahahahah\",\"telephone\":\"+21655552500\",\"horaires\":\"09:00-21:00\",\"location_match\":false,\"distance_km\":1.96,\"matched_meals_count\":1,\"safe_meals_count\":1,\"allergy_conflict_meals\":0,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":0,\"regime_warning_meals\":0,\"unknown_meals_count\":0,\"has_free_options\":true,\"has_paid_options\":false,\"matched_meals\":[{\"meal_id\":\"meal_20260420113202_b9ee4f\",\"meal_name\":\"pad thai\",\"ingredients\":\"cacahuete, nouille, sauce, carotte, champignon, poulet\",\"quantity\":7,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"bio, halal\",\"allergens\":\"cacahuete\",\"regime_overlap\":1,\"semantic_regimes\":[\"bio\",\"halal\"],\"semantic_allergens\":[\"arachide\"],\"declared_allergens_semantic\":[\"arachide\"],\"inferred_allergens_semantic\":[],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"declared\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"declared\",\"ai_confidence\":1,\"ai_explanation\":\"Allergenes declares par le vendeur. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":false,\"ai_legal_warning\":\"\",\"ai_allergen_sources\":{\"arachide\":[\"cacahuete\"]},\"ai_allergen_source_scopes\":{\"arachide\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"arachide\",\"trigger_ingredients\":[\"cacahuete\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":1,\"data_quality_confidence\":1,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":100,\"ai_data_quality_confidence\":1,\"ai_allergen_inference_confidence\":1,\"ai_check_passed\":true,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":1,\"inference_confidence\":1},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false}],\"score\":23,\"matching_brief\":{\"engine\":\"caremeal-hybrid-ai-gemini\",\"safety_mode\":\"strict\",\"user_allergies_raw\":\"chocolat au lait\",\"user_allergies_semantic\":[\"lactose\"],\"user_allergy_keywords\":[\"chocolat-au-lait\",\"chocolat\",\"chocolat-lait\",\"lait\"],\"user_allergy_input_map\":{\"chocolat au lait\":[\"lactose\",\"soja\"]},\"user_input_mapping\":[{\"raw_term\":\"chocolat au lait\",\"exact_keyword\":\"chocolat-au-lait\",\"mapped_allergens\":[\"lactose\"],\"mapped_evidence\":[],\"mapped_all_tokens\":[\"lactose\"]}],\"user_regimes_semantic\":[\"halal\",\"vegetarien\"],\"score_reasons\":[\"+19 regime compatibility\",\"+2 safe meals count\",\"+2 semantic coverage\"],\"analysis_mode\":\"ai_live\"}}],\"applied_filters\":{\"price_mode\":\"all\",\"sort_by\":\"score\",\"safety_mode\":\"strict\",\"semantic_engine\":\"caremeal-hybrid-ai-gemini\"}}', '2026-05-07 11:08:01', '2026-05-07 11:08:01');
INSERT INTO `matching_snapshot` (`id`, `id_user`, `id_pref`, `price_mode`, `sort_by`, `safety_mode`, `source_version`, `payload_json`, `created_at`, `updated_at`) VALUES
(13, 1, 29, 'all', 'score', 'souple', 27, '{\"preference\":{\"id_pref\":29,\"regime_alimentaire\":\"halal, vegetarien\",\"allergies\":\"chocolat au lait\",\"localisation\":\"Rue Pasteur, Ennasr 1, Nasr 1, Délégation Ariana Ville, Gouvernorat Ariana, 2037, Tunisie\",\"date_demande\":\"2026-05-07 12:07:16.000000\",\"id_user\":1},\"restaurants\":[{\"id_restaurant\":4,\"id_owner\":1,\"nom\":\"dar louay\",\"localisation\":\"Office National Des Huiles, Rue du 13 Aout, Ezzouhour, Bach-Hamba, Délégation Ezzouhour, Tunis, Gouvernorat Tunis, 2052, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260428_122516_0ec9a21a.jpg\",\"description\":\"ENA NOSKON FI BARDO BAHDHA L KLEB NBI3 CHAWARMA HOT DOG\",\"telephone\":\"+21698910180\",\"horaires\":\"11:00-20:00\",\"location_match\":false,\"distance_km\":6.84,\"matched_meals_count\":3,\"safe_meals_count\":3,\"allergy_conflict_meals\":0,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":0,\"regime_warning_meals\":0,\"unknown_meals_count\":0,\"has_free_options\":true,\"has_paid_options\":true,\"matched_meals\":[{\"meal_id\":\"meal_20260428122653_9dd0f1\",\"meal_name\":\"chawarma\",\"ingredients\":\"viande dinde, tomate, salade grillée, oignons, harissa, choux, pain libanais\",\"quantity\":6,\"pricing_mode\":\"paid\",\"price\":50,\"regime_tags\":\"bio, halal\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"bio\",\"halal\"],\"semantic_allergens\":[\"gluten\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"gluten\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"gluten\":[\"pain libanais\"]},\"ai_allergen_source_scopes\":{\"gluten\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"gluten\",\"trigger_ingredients\":[\"pain libanais\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false},{\"meal_id\":\"meal_20260505152344_adb8c9\",\"meal_name\":\"plat tunisien\",\"ingredients\":\"salade fraiche, salade grillée, thon, oeuf, harissa, mayonnaise\",\"quantity\":10,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"halal\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"halal\"],\"semantic_allergens\":[\"poisson\",\"oeuf\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"poisson\",\"oeuf\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"poisson\":[\"thon\"],\"oeuf\":[\"oeuf\",\"mayonnaise\"]},\"ai_allergen_source_scopes\":{\"poisson\":[\"vendor_ingredients\"],\"oeuf\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"poisson\",\"trigger_ingredients\":[\"thon\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"},{\"allergen\":\"oeuf\",\"trigger_ingredients\":[\"oeuf\",\"mayonnaise\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false},{\"meal_id\":\"meal_20260507104253_f8881b\",\"meal_name\":\"kafteji\",\"ingredients\":\"patate, oeufs, poivron vert, huile vegetale\",\"quantity\":6,\"pricing_mode\":\"paid\",\"price\":4,\"regime_tags\":\"halal, vegetarien\",\"allergens\":\"\",\"regime_overlap\":2,\"semantic_regimes\":[\"halal\",\"vegetarien\"],\"semantic_allergens\":[],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":[],\"ai_allergen_source_scopes\":[],\"ai_causal_allergen_details\":[],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false}],\"score\":80,\"matching_brief\":{\"engine\":\"caremeal-local-semantics\",\"safety_mode\":\"souple\",\"user_allergies_raw\":\"chocolat au lait\",\"user_allergies_semantic\":[\"chocolat\",\"lactose\"],\"user_allergy_keywords\":[\"chocolat-au-lait\",\"chocolat\",\"chocolat-lait\",\"lait\"],\"user_allergy_input_map\":{\"chocolat au lait\":[\"chocolat\",\"lactose\",\"soja\"]},\"user_input_mapping\":[{\"raw_term\":\"chocolat au lait\",\"exact_keyword\":\"chocolat\",\"mapped_allergens\":[\"chocolat\",\"lactose\"],\"mapped_evidence\":[],\"mapped_all_tokens\":[\"chocolat\",\"lactose\"]}],\"user_regimes_semantic\":[\"halal\",\"vegetarien\"],\"score_reasons\":[\"+70 regime compatibility\",\"+6 safe meals count\",\"+4 semantic coverage\"],\"analysis_mode\":\"ai_live\"}},{\"id_restaurant\":6,\"id_owner\":1,\"nom\":\"sugar overload\",\"localisation\":\"La Marsa, Tunis, Gouvernorat Tunis, 2070, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260505_152835_f58fe7bd.jpg\",\"description\":\"BIENVENUE ICI TESTSTSTTSTSTSTSTSTSTTSTSTSTSSTST\",\"telephone\":\"+21638902918\",\"horaires\":\"10:00-22:00\",\"location_match\":false,\"distance_km\":6.84,\"matched_meals_count\":3,\"safe_meals_count\":1,\"allergy_conflict_meals\":1,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":1,\"regime_warning_meals\":1,\"unknown_meals_count\":0,\"has_free_options\":true,\"has_paid_options\":false,\"matched_meals\":[{\"meal_id\":\"meal_20260507072209_922713\",\"meal_name\":\"tarte aux fruits\",\"ingredients\":\"fruits\",\"quantity\":7,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"fruits\",\"allergens\":\"\",\"regime_overlap\":0,\"semantic_regimes\":[],\"semantic_allergens\":[],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":false,\"warning_regime\":true,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":true,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":[],\"ai_allergen_source_scopes\":[],\"ai_causal_allergen_details\":[],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false},{\"meal_id\":\"meal_20260507104422_870f35\",\"meal_name\":\"brownies\",\"ingredients\":\"chocolat\",\"quantity\":20,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"halal, vegetarien\",\"allergens\":\"\",\"regime_overlap\":2,\"semantic_regimes\":[\"halal\",\"vegetarien\"],\"semantic_allergens\":[],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":true,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":true,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":[],\"ai_allergen_source_scopes\":[],\"ai_causal_allergen_details\":[],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[{\"type\":\"keyword_exact\",\"raw_term\":\"chocolat au lait\",\"allergen\":\"chocolat\",\"origin\":\"text_direct\",\"origin_label\":\"Correspondance preference\",\"trigger_ingredients\":[\"chocolat\"],\"source_scopes\":[\"text_direct\"],\"confidence\":1},{\"type\":\"keyword_exact\",\"raw_term\":\"chocolat\",\"allergen\":\"chocolat\",\"origin\":\"text_direct\",\"origin_label\":\"Correspondance preference\",\"trigger_ingredients\":[\"chocolat\"],\"source_scopes\":[\"text_direct\"],\"confidence\":0.95}],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[\"chocolat\"],\"trace_risk\":false},{\"meal_id\":\"meal_20260507110758_650d14\",\"meal_name\":\"kounefa\",\"ingredients\":\"fromage\",\"quantity\":10,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"vegetarien\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"vegetarien\"],\"semantic_allergens\":[\"lactose\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"lactose\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":true,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":true,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"lactose\":[\"fromage\"]},\"ai_allergen_source_scopes\":{\"lactose\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"lactose\",\"trigger_ingredients\":[\"fromage\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[{\"type\":\"ai_inferred\",\"raw_term\":\"chocolat au lait\",\"allergen\":\"lactose\",\"origin\":\"ai_precomputed\",\"origin_label\":\"Analyse IA pre-calculee\",\"trigger_ingredients\":[\"fromage\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55}],\"ai_potential_conflict\":true,\"ai_conflict_tokens\":[\"lactose\"],\"trace_risk\":false}],\"score\":42,\"matching_brief\":{\"engine\":\"caremeal-local-semantics\",\"safety_mode\":\"souple\",\"user_allergies_raw\":\"chocolat au lait\",\"user_allergies_semantic\":[\"chocolat\",\"lactose\"],\"user_allergy_keywords\":[\"chocolat-au-lait\",\"chocolat\",\"chocolat-lait\",\"lait\"],\"user_allergy_input_map\":{\"chocolat au lait\":[\"chocolat\",\"lactose\",\"soja\"]},\"user_input_mapping\":[{\"raw_term\":\"chocolat au lait\",\"exact_keyword\":\"chocolat\",\"mapped_allergens\":[\"chocolat\",\"lactose\"],\"mapped_evidence\":[],\"mapped_all_tokens\":[\"chocolat\",\"lactose\"]}],\"user_regimes_semantic\":[\"halal\",\"vegetarien\"],\"score_reasons\":[\"+51 regime compatibility\",\"+2 safe meals count\",\"-5 allergy conflicts excluded\",\"+2 semantic coverage\",\"-8 allergenes potentiels IA (mode souple)\",\"-4 meals hors regime (mode souple)\"],\"analysis_mode\":\"ai_live\"}},{\"id_restaurant\":5,\"id_owner\":1,\"nom\":\"chez hamido\",\"localisation\":\"Lycée Khaznadar, Avenue de l\'Indépendance, Khaznadar, Délégation Le Bardo, Tunis, Gouvernorat Tunis, 2017, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260428_151324_2540233c.png\",\"description\":\"JE SUIS UN RESTOTOTOTOTOTOTOTTOTOTOTOTO\",\"telephone\":\"+21698437272\",\"horaires\":\"09:08-15:00\",\"location_match\":false,\"distance_km\":6.72,\"matched_meals_count\":2,\"safe_meals_count\":2,\"allergy_conflict_meals\":0,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":2,\"regime_warning_meals\":0,\"unknown_meals_count\":0,\"has_free_options\":false,\"has_paid_options\":true,\"matched_meals\":[{\"meal_id\":\"meal_20260503202442_c3496a\",\"meal_name\":\"cheese burger\",\"ingredients\":\"viande, laitue, tomate, ketchup, mayonnaise, fromage cheddar\",\"quantity\":15,\"pricing_mode\":\"paid\",\"price\":3,\"regime_tags\":\"equilibre, halal\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"equilibre\",\"halal\"],\"semantic_allergens\":[\"lactose\",\"oeuf\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"lactose\",\"oeuf\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":true,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":true,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"lactose\":[\"cheese burger\",\"fromage cheddar\"],\"oeuf\":[\"mayonnaise\"]},\"ai_allergen_source_scopes\":{\"lactose\":[\"vendor_ingredients\"],\"oeuf\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"lactose\",\"trigger_ingredients\":[\"cheese burger\",\"fromage cheddar\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"},{\"allergen\":\"oeuf\",\"trigger_ingredients\":[\"mayonnaise\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[{\"type\":\"ai_inferred\",\"raw_term\":\"chocolat au lait\",\"allergen\":\"lactose\",\"origin\":\"ai_precomputed\",\"origin_label\":\"Analyse IA pre-calculee\",\"trigger_ingredients\":[\"cheese burger\",\"fromage cheddar\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55}],\"ai_potential_conflict\":true,\"ai_conflict_tokens\":[\"lactose\"],\"trace_risk\":false},{\"meal_id\":\"meal_20260503202841_130fc7\",\"meal_name\":\"croque madame jambon\",\"ingredients\":\"pain de mie, oeuf, jambon, fromage mozza, mayonnaise\",\"quantity\":12,\"pricing_mode\":\"paid\",\"price\":2,\"regime_tags\":\"halal, budget\",\"allergens\":\"\",\"regime_overlap\":1,\"semantic_regimes\":[\"halal\",\"budget\"],\"semantic_allergens\":[\"gluten\",\"lactose\",\"oeuf\"],\"declared_allergens_semantic\":[],\"inferred_allergens_semantic\":[\"gluten\",\"lactose\",\"oeuf\"],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"none\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":true,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":true,\"ai_source\":\"ingredients\",\"ai_confidence\":0.55,\"ai_explanation\":\"Scan rapide de securite applique. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":true,\"ai_legal_warning\":\"Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.\",\"ai_allergen_sources\":{\"gluten\":[\"pain de mie\"],\"oeuf\":[\"oeuf\",\"mayonnaise\"],\"lactose\":[\"fromage mozza\"]},\"ai_allergen_source_scopes\":{\"gluten\":[\"vendor_ingredients\"],\"oeuf\":[\"vendor_ingredients\"],\"lactose\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"gluten\",\"trigger_ingredients\":[\"pain de mie\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"},{\"allergen\":\"lactose\",\"trigger_ingredients\":[\"fromage mozza\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"},{\"allergen\":\"oeuf\",\"trigger_ingredients\":[\"oeuf\",\"mayonnaise\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55,\"data_quality_confidence\":0.55,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":55,\"ai_data_quality_confidence\":0.55,\"ai_allergen_inference_confidence\":0.55,\"ai_check_passed\":false,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":0.55,\"inference_confidence\":0.55},\"conflict_hard\":[],\"conflict_soft\":[{\"type\":\"ai_inferred\",\"raw_term\":\"chocolat au lait\",\"allergen\":\"lactose\",\"origin\":\"ai_precomputed\",\"origin_label\":\"Analyse IA pre-calculee\",\"trigger_ingredients\":[\"fromage mozza\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":0.55}],\"ai_potential_conflict\":true,\"ai_conflict_tokens\":[\"lactose\"],\"trace_risk\":false}],\"score\":30,\"matching_brief\":{\"engine\":\"caremeal-local-semantics\",\"safety_mode\":\"souple\",\"user_allergies_raw\":\"chocolat au lait\",\"user_allergies_semantic\":[\"chocolat\",\"lactose\"],\"user_allergy_keywords\":[\"chocolat-au-lait\",\"chocolat\",\"chocolat-lait\",\"lait\"],\"user_allergy_input_map\":{\"chocolat au lait\":[\"chocolat\",\"lactose\",\"soja\"]},\"user_input_mapping\":[{\"raw_term\":\"chocolat au lait\",\"exact_keyword\":\"chocolat\",\"mapped_allergens\":[\"chocolat\",\"lactose\"],\"mapped_evidence\":[],\"mapped_all_tokens\":[\"chocolat\",\"lactose\"]}],\"user_regimes_semantic\":[\"halal\",\"vegetarien\"],\"score_reasons\":[\"+38 regime compatibility\",\"+4 safe meals count\",\"+4 semantic coverage\",\"-16 allergenes potentiels IA (mode souple)\"],\"analysis_mode\":\"ai_live\"}},{\"id_restaurant\":1,\"id_owner\":1,\"nom\":\"elvilla\",\"localisation\":\"El Menzah 5, Délégation Ariana Ville, Gouvernorat Ariana, 2091, Tunisie\",\"image_path\":\"assets\\/restaurant-signs\\/restaurant_20260420_110902_99e7f991.png\",\"description\":\"hahahahahaahahahahah\",\"telephone\":\"+21655552500\",\"horaires\":\"09:00-21:00\",\"location_match\":false,\"distance_km\":1.96,\"matched_meals_count\":1,\"safe_meals_count\":1,\"allergy_conflict_meals\":0,\"trace_risk_meals\":0,\"ai_potential_conflict_meals\":0,\"regime_warning_meals\":0,\"unknown_meals_count\":0,\"has_free_options\":true,\"has_paid_options\":false,\"matched_meals\":[{\"meal_id\":\"meal_20260420113202_b9ee4f\",\"meal_name\":\"pad thai\",\"ingredients\":\"cacahuete, nouille, sauce, carotte, champignon, poulet\",\"quantity\":7,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"bio, halal\",\"allergens\":\"cacahuete\",\"regime_overlap\":1,\"semantic_regimes\":[\"bio\",\"halal\"],\"semantic_allergens\":[\"arachide\"],\"declared_allergens_semantic\":[\"arachide\"],\"inferred_allergens_semantic\":[],\"inferred_ingredients\":[],\"missing_ingredients\":[],\"allergen_disclosure_mode\":\"declared\",\"regime_compatible\":true,\"warning_regime\":false,\"warning_allergene_hard\":false,\"warning_allergene_soft\":false,\"warning_unknown\":false,\"warning_unknown_message\":\"\",\"has_any_warning\":false,\"ai_source\":\"declared\",\"ai_confidence\":1,\"ai_explanation\":\"Allergenes declares par le vendeur. Cliquez pour details IA complets.\",\"ai_origin_badge\":\"AI pre-computed\",\"ai_origin_code\":\"ai_precomputed\",\"ai_warning\":false,\"ai_legal_warning\":\"\",\"ai_allergen_sources\":{\"arachide\":[\"cacahuete\"]},\"ai_allergen_source_scopes\":{\"arachide\":[\"vendor_ingredients\"]},\"ai_causal_allergen_details\":[{\"allergen\":\"arachide\",\"trigger_ingredients\":[\"cacahuete\"],\"source_scopes\":[\"vendor_ingredients\"],\"confidence\":1,\"data_quality_confidence\":1,\"origin\":\"ai_precomputed\"}],\"ai_possible_missing_ingredients\":false,\"ai_basic_recipe_ingredients\":[],\"ai_basic_recipe_allergens\":[],\"ai_ingredient_completeness_score\":100,\"ai_data_quality_confidence\":1,\"ai_allergen_inference_confidence\":1,\"ai_check_passed\":true,\"analysis_mode\":\"ai_live\",\"recipe_baseline\":[],\"recipe_variants\":[],\"confidence_split\":{\"data_quality_confidence\":1,\"inference_confidence\":1},\"conflict_hard\":[],\"conflict_soft\":[],\"ai_potential_conflict\":false,\"ai_conflict_tokens\":[],\"trace_risk\":false}],\"score\":23,\"matching_brief\":{\"engine\":\"caremeal-local-semantics\",\"safety_mode\":\"souple\",\"user_allergies_raw\":\"chocolat au lait\",\"user_allergies_semantic\":[\"chocolat\",\"lactose\"],\"user_allergy_keywords\":[\"chocolat-au-lait\",\"chocolat\",\"chocolat-lait\",\"lait\"],\"user_allergy_input_map\":{\"chocolat au lait\":[\"chocolat\",\"lactose\",\"soja\"]},\"user_input_mapping\":[{\"raw_term\":\"chocolat au lait\",\"exact_keyword\":\"chocolat\",\"mapped_allergens\":[\"chocolat\",\"lactose\"],\"mapped_evidence\":[],\"mapped_all_tokens\":[\"chocolat\",\"lactose\"]}],\"user_regimes_semantic\":[\"halal\",\"vegetarien\"],\"score_reasons\":[\"+19 regime compatibility\",\"+2 safe meals count\",\"+2 semantic coverage\"],\"analysis_mode\":\"ai_live\"}}],\"applied_filters\":{\"price_mode\":\"all\",\"sort_by\":\"score\",\"safety_mode\":\"souple\",\"semantic_engine\":\"caremeal-local-semantics\"}}', '2026-05-07 12:25:11', '2026-05-07 12:25:11');

-- --------------------------------------------------------

--
-- Structure de la table `matching_source_version`
--

CREATE TABLE `matching_source_version` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `source_version` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `matching_source_version`
--

INSERT INTO `matching_source_version` (`id`, `source_version`, `updated_at`) VALUES
(1, 27, '2026-05-07 11:07:16');

-- --------------------------------------------------------

--
-- Structure de la table `offre`
--

CREATE TABLE `offre` (
  `id_offre` int(11) NOT NULL,
  `titre` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL,
  `prix_original` decimal(10,2) NOT NULL,
  `photo_url` longtext DEFAULT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `heure_debut` time DEFAULT NULL,
  `heure_fin` time DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `statut` enum('publiee','brouillon','archivee','expiree') DEFAULT 'publiee',
  `id_categorie` int(11) DEFAULT NULL,
  `id_partenaire` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `offre`
--

INSERT INTO `offre` (`id_offre`, `titre`, `description`, `prix`, `prix_original`, `photo_url`, `quantite`, `heure_debut`, `heure_fin`, `date_creation`, `date_modification`, `statut`, `id_categorie`, `id_partenaire`) VALUES
(128, 'loulou', 'eeee', 4.29, 12.00, '', 4, '22:00:00', '23:00:00', '2026-05-07 20:12:09', '2026-05-07 20:12:09', 'publiee', 62, NULL),
(139, 'sandwich', 'zdzdzdz', 4.30, 1200.00, '', 4, '20:32:00', '23:00:00', '2026-05-08 19:22:09', '2026-05-08 19:22:09', 'publiee', 70, '5');

-- --------------------------------------------------------

--
-- Structure de la table `participation`
--

CREATE TABLE `participation` (
  `id_participation` int(11) NOT NULL,
  `evenement_id` int(11) NOT NULL,
  `etudiant_id` int(11) NOT NULL,
  `date_inscription` date NOT NULL,
  `statut` varchar(20) NOT NULL DEFAULT 'Inscrit'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `participation`
--

INSERT INTO `participation` (`id_participation`, `evenement_id`, `etudiant_id`, `date_inscription`, `statut`) VALUES
(3, 8, 2, '2026-05-07', 'Annulé'),
(4, 10, 2, '2026-05-07', 'Inscrit');

-- --------------------------------------------------------

--
-- Structure de la table `planning_collecte`
--

CREATE TABLE `planning_collecte` (
  `id_collecte` int(11) NOT NULL,
  `id_restaurant` int(11) NOT NULL,
  `id_pref` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `mode_collecte` varchar(20) NOT NULL,
  `adresse_livraison` varchar(255) DEFAULT NULL,
  `adresse_lat` decimal(10,7) DEFAULT NULL,
  `adresse_lng` decimal(10,7) DEFAULT NULL,
  `pref_regime_snapshot` varchar(1000) NOT NULL DEFAULT '',
  `pref_allergies_snapshot` varchar(1000) NOT NULL DEFAULT '',
  `pref_localisation_snapshot` varchar(1000) NOT NULL DEFAULT '',
  `delivery_driver_first_name` varchar(100) DEFAULT NULL,
  `delivery_driver_last_name` varchar(100) DEFAULT NULL,
  `delivery_driver_contact` varchar(40) DEFAULT NULL,
  `delivery_tracking_token` varchar(120) DEFAULT NULL,
  `delivery_tracker_client_id` varchar(80) DEFAULT NULL,
  `delivery_driver_lat` decimal(10,7) DEFAULT NULL,
  `delivery_driver_lng` decimal(10,7) DEFAULT NULL,
  `delivery_driver_updated_at` datetime DEFAULT NULL,
  `heure_demande` datetime NOT NULL DEFAULT current_timestamp(),
  `heure_souhaitee` datetime NOT NULL,
  `statut` varchar(30) NOT NULL DEFAULT 'en_attente',
  `items_json` longtext NOT NULL,
  `montant_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `planning_collecte`
--

INSERT INTO `planning_collecte` (`id_collecte`, `id_restaurant`, `id_pref`, `id_user`, `mode_collecte`, `adresse_livraison`, `adresse_lat`, `adresse_lng`, `pref_regime_snapshot`, `pref_allergies_snapshot`, `pref_localisation_snapshot`, `delivery_driver_first_name`, `delivery_driver_last_name`, `delivery_driver_contact`, `delivery_tracking_token`, `delivery_tracker_client_id`, `delivery_driver_lat`, `delivery_driver_lng`, `delivery_driver_updated_at`, `heure_demande`, `heure_souhaitee`, `statut`, `items_json`, `montant_total`, `created_at`, `updated_at`) VALUES
(22, 1, 28, 1, 'delivery', 'Rue Pasteur, Ennasr 1, Nasr 1, Délégation Ariana Ville, Gouvernorat Ariana, 2037, Tunisie', 36.8682490, 10.1729710, 'fruits, halal, vegetarien', 'chocolat', 'École Superieur Privée d\'Ingénierie et de Technologie (ESPRIT), Rue de Newton, Zone Industrielle Chotrana II, Nkhilet, Délégation Raoued, Gouvernorat Ariana, 2088, Tunisie', 'meddeb', 'yasmine', '+21655552500', 'd168921d58a188bc906074214c65cce1', 'trk_swo3ye4hr3rj0b', 36.8680900, 10.1725650, '2026-05-07 11:40:01', '2026-05-07 10:40:34', '2026-05-07 10:00:00', 'en_cours_livraison', '[{\"meal_id\":\"meal_20260420113202_b9ee4f\",\"meal_name\":\"pad thai\",\"quantity\":1,\"pricing_mode\":\"free\",\"unit_price\":\"0.00\",\"line_total\":\"0.00\"}]', 0.00, '2026-05-07 10:40:34', '2026-05-07 11:40:01'),
(23, 4, 28, 1, 'pickup', NULL, NULL, NULL, 'fruits, halal, vegetarien', 'chocolat', 'École Superieur Privée d\'Ingénierie et de Technologie (ESPRIT), Rue de Newton, Zone Industrielle Chotrana II, Nkhilet, Délégation Raoued, Gouvernorat Ariana, 2088, Tunisie', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-07 10:42:46', '2026-05-07 11:09:00', 'en_attente', '[{\"meal_id\":\"meal_20260428122653_9dd0f1\",\"meal_name\":\"chawarma\",\"quantity\":1,\"pricing_mode\":\"paid\",\"unit_price\":\"50.00\",\"line_total\":\"50.00\"}]', 50.00, '2026-05-07 10:42:46', '2026-05-07 10:42:46');

-- --------------------------------------------------------

--
-- Structure de la table `preference`
--

CREATE TABLE `preference` (
  `id_pref` int(100) NOT NULL,
  `regime_alimentaire` varchar(1000) NOT NULL,
  `allergies` varchar(1000) NOT NULL,
  `localisation` varchar(1000) NOT NULL,
  `date_demande` datetime(6) NOT NULL,
  `id_user` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `preference`
--

INSERT INTO `preference` (`id_pref`, `regime_alimentaire`, `allergies`, `localisation`, `date_demande`, `id_user`) VALUES
(22, 'bio, hheheheeh', 'soy sauce', 'Colisée Soula, El Manar 1, El Manar, Délégation El Menzah, Tunis, Gouvernorat Tunis, 7102, Tunisie', '2026-04-28 00:43:06.000000', 1),
(23, 'fruits, halal', 'web', 'Bardo, Délégation Le Bardo, Gouvernorat Tunis, 2000, Tunisie', '2026-04-28 15:24:01.000000', 1),
(24, 'budget', 'cacahuete', 'Avenue Jallouli Fares, Ennasr 1, Nasr 1, Délégation Ariana Ville, Gouvernorat Ariana, 2037, Tunisie', '2026-04-28 15:23:19.000000', 1),
(25, 'halal', 'gluten', 'Rue Pasteur, Ennasr 1, Nasr 1, Délégation Ariana Ville, Gouvernorat Ariana, 2037, Tunisie', '2026-05-05 02:48:53.000000', 1),
(26, 'halal', 'bread', 'Rue Pasteur, Ennasr 1, Nasr 1, Délégation Ariana Ville, Gouvernorat Ariana, 2037, Tunisie', '2026-05-05 08:18:35.000000', 1),
(27, 'halal', 'cheesecake', 'Rue Pasteur, Ennasr 1, Nasr 1, Délégation Ariana Ville, Gouvernorat Ariana, 2037, Tunisie', '2026-05-05 08:57:38.000000', 1),
(28, 'fruits, halal, vegetarien', 'chocolat', 'École Superieur Privée d\'Ingénierie et de Technologie (ESPRIT), Rue de Newton, Zone Industrielle Chotrana II, Nkhilet, Délégation Raoued, Gouvernorat Ariana, 2088, Tunisie', '2026-05-05 15:59:23.000000', 1),
(29, 'halal, vegetarien', 'chocolat au lait', 'Rue Pasteur, Ennasr 1, Nasr 1, Délégation Ariana Ville, Gouvernorat Ariana, 2037, Tunisie', '2026-05-07 12:07:16.000000', 1);

-- --------------------------------------------------------

--
-- Structure de la table `produit`
--

CREATE TABLE `produit` (
  `id_produit` int(10) UNSIGNED NOT NULL,
  `id_categorie` int(10) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `prix_normal` decimal(10,2) NOT NULL,
  `prix_commande` decimal(10,2) NOT NULL,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `produit`
--

INSERT INTO `produit` (`id_produit`, `id_categorie`, `nom`, `description`, `prix_normal`, `prix_commande`, `stock`, `actif`, `created_at`, `updated_at`) VALUES
(1, 2, 'pain', 'blé', 0.80, 0.30, 6, 1, '2026-05-10 00:06:14', '2026-05-10 09:20:03'),
(2, 5, 'salade cesar', '', 13.00, 7.00, 3, 1, '2026-05-10 00:07:42', '2026-05-10 00:17:26'),
(3, 2, 'Panier viennoiseries mix', 'gateau, cake au chocolat', 5.00, 3.00, 0, 1, '2026-05-10 00:18:02', '2026-05-10 01:15:14'),
(4, 2, 'pain2', '', 0.90, 0.40, 6, 0, '2026-05-10 00:28:49', '2026-05-10 01:04:23'),
(5, 4, 'Soupe', '', 7.00, 4.50, 5, 1, '2026-05-10 00:34:54', '2026-05-10 09:19:56'),
(6, 2, 'croissant', 'chocolat', 1.50, 1.00, 3, 1, '2026-05-10 00:49:25', '2026-05-10 07:32:45'),
(7, 3, 'cake', 'red velvet', 11.00, 4.00, 2, 1, '2026-05-10 09:24:04', '2026-05-10 09:24:32'),
(9, 3, 'brookie', 'brownies and cookies mix', 7.00, 3.00, 3, 1, '2026-05-10 09:30:46', '2026-05-10 09:31:32');

-- --------------------------------------------------------

--
-- Structure de la table `profiles`
--

CREATE TABLE `profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL DEFAULT '',
  `prenom` varchar(100) NOT NULL DEFAULT '',
  `telephone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `github` varchar(255) DEFAULT NULL,
  `ecole` varchar(150) DEFAULT NULL,
  `annee_etude` varchar(50) DEFAULT NULL,
  `quartier` varchar(100) DEFAULT NULL,
  `points_accumules` int(11) NOT NULL DEFAULT 0,
  `nom_entreprise` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `site_web` varchar(255) DEFAULT NULL,
  `secteur_activite` varchar(100) DEFAULT NULL,
  `face_descriptor` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `profiles`
--

INSERT INTO `profiles` (`id`, `user_id`, `nom`, `prenom`, `telephone`, `avatar`, `linkedin`, `instagram`, `facebook`, `twitter`, `github`, `ecole`, `annee_etude`, `quartier`, `points_accumules`, `nom_entreprise`, `description`, `site_web`, `secteur_activite`, `face_descriptor`, `created_at`) VALUES
(1, 1, 'Admin', 'CareMeal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-05-07 14:07:02'),
(2, 2, 'aziz', 'ben brahem', '12345678', NULL, NULL, NULL, NULL, NULL, NULL, 'INSAT', '3eme', 'Marsa', 0, NULL, NULL, NULL, NULL, NULL, '2026-05-07 14:39:40'),
(3, 3, 'aziz', 'ben brahem', '12345678', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'test', 'ujh', NULL, NULL, NULL, '2026-05-07 14:44:52'),
(5, 4, 'Louay', 'zarrouk', '98910180', NULL, NULL, NULL, NULL, NULL, NULL, 'ESPRIT', '1ere', 'Bardo', 0, NULL, NULL, NULL, NULL, NULL, '2026-05-08 08:00:00'),
(6, 5, 'Louay', 'zarrouk', '+21698910180', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'zarrouk Louay', 'efefef', NULL, NULL, NULL, '2026-05-08 08:19:31'),
(7, 6, 'Ben Salah', 'Hadil', NULL, 'https://lh3.googleusercontent.com/a/ACg8ocITSO7IZv1W4YSanoFzeNYmWtwnboReM3qJoqo6j2lAxpoXNA=s96-c', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-05-09 13:06:51'),
(8, 7, 'Ben Salah', 'Hadil', NULL, 'https://lh3.googleusercontent.com/a/ACg8ocITSO7IZv1W4YSanoFzeNYmWtwnboReM3qJoqo6j2lAxpoXNA=s96-c', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-05-09 13:12:43'),
(9, 8, 'Ben Salah', 'Hadil', '28257013', NULL, NULL, NULL, NULL, NULL, NULL, 'ESPRIT', '2eme', 'Manouba', 0, NULL, NULL, NULL, NULL, NULL, '2026-05-09 13:29:46');

-- --------------------------------------------------------

--
-- Structure de la table `publication`
--

CREATE TABLE `publication` (
  `id_publication` int(11) NOT NULL,
  `contenu_publication` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `date_publication` datetime DEFAULT current_timestamp(),
  `auteur_type` enum('etudiant','partenaire','admin') NOT NULL,
  `auteur_id` varchar(50) DEFAULT NULL,
  `moderation_status` varchar(20) NOT NULL DEFAULT 'approved',
  `moderation_risk` varchar(20) DEFAULT NULL,
  `moderation_reason` varchar(255) DEFAULT NULL,
  `moderation_provider` varchar(50) NOT NULL DEFAULT 'gemini'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `publication_reaction`
--

CREATE TABLE `publication_reaction` (
  `id` int(11) NOT NULL,
  `publication_id` int(11) NOT NULL,
  `auteur_type` varchar(50) NOT NULL,
  `auteur_id` varchar(50) NOT NULL,
  `reaction` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `publication_reaction`
--

INSERT INTO `publication_reaction` (`id`, `publication_id`, `auteur_type`, `auteur_id`, `reaction`, `created_at`) VALUES
(1, 1, 'etudiant', '8', 'Love', '2026-05-09 20:48:41'),
(2, 2, 'etudiant', '8', 'Love', '2026-05-10 07:45:50');

-- --------------------------------------------------------

--
-- Structure de la table `regime_option`
--

CREATE TABLE `regime_option` (
  `id_option` int(11) NOT NULL,
  `option_value` varchar(100) NOT NULL,
  `option_label` varchar(100) NOT NULL,
  `icon_class` varchar(80) NOT NULL DEFAULT 'fa-utensils',
  `icon_type` varchar(20) NOT NULL DEFAULT 'fa',
  `icon_image_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `regime_option`
--

INSERT INTO `regime_option` (`id_option`, `option_value`, `option_label`, `icon_class`, `icon_type`, `icon_image_path`, `created_at`) VALUES
(1, 'halal', 'Halal', 'fa-star-and-crescent', 'fa', NULL, '2026-04-19 16:59:53'),
(2, 'vegetarien', 'Vegetarien', 'fa-leaf', 'fa', NULL, '2026-04-19 16:59:53'),
(3, 'vegan', 'Vegan', 'fa-seedling', 'fa', NULL, '2026-04-19 16:59:53'),
(4, 'sans-gluten', 'Sans gluten', 'fa-wheat-awn', 'fa', NULL, '2026-04-19 16:59:53'),
(5, 'bio', 'Bio', 'fa-spa', 'fa', NULL, '2026-04-19 16:59:53'),
(6, 'sans-lactose', 'Sans lactose', 'fa-glass-water', 'fa', NULL, '2026-04-19 16:59:53'),
(7, 'budget', 'Petit budget', 'fa-coins', 'fa', NULL, '2026-04-19 16:59:53'),
(8, 'equilibre', 'Equilibre', 'fa-scale-balanced', 'fa', NULL, '2026-04-19 16:59:53'),
(9, 'hheheheeh', 'Hheheheeh', 'fa-utensils', 'fa', NULL, '2026-04-19 17:14:15'),
(12, 'fruits', 'Fruits', 'fa-apple-whole', 'fa', NULL, '2026-04-28 11:34:05'),
(13, 'louay', 'Louay', 'fa-image', 'image', 'assets/regime-icons/regime_20260428_123741_6ebfa169.png', '2026-04-28 11:36:37');

-- --------------------------------------------------------

--
-- Structure de la table `restaurant`
--

CREATE TABLE `restaurant` (
  `id_restaurant` int(11) NOT NULL,
  `id_owner` int(11) NOT NULL,
  `nom` varchar(120) NOT NULL,
  `localisation` varchar(180) NOT NULL,
  `localisation_lat` decimal(10,7) DEFAULT NULL,
  `localisation_lng` decimal(10,7) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `telephone` varchar(30) NOT NULL,
  `horaires` varchar(120) NOT NULL,
  `meals_json` longtext DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `restaurant`
--

INSERT INTO `restaurant` (`id_restaurant`, `id_owner`, `nom`, `localisation`, `localisation_lat`, `localisation_lng`, `image_path`, `description`, `telephone`, `horaires`, `meals_json`, `actif`, `created_at`, `updated_at`) VALUES
(1, 1, 'elvilla', 'El Menzah 5, Délégation Ariana Ville, Gouvernorat Ariana, 2091, Tunisie', 36.8498396, 10.1717562, 'assets/restaurant-signs/restaurant_20260420_110902_99e7f991.png', 'hahahahahaahahahahah', '+21655552500', '09:00-21:00', '[{\"meal_name\":\"pad thai\",\"ingredients\":\"cacahuete, nouille, sauce, carotte, champignon, poulet\",\"quantity\":8,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"bio, halal\",\"allergen_disclosure_mode\":\"declared\",\"allergens\":\"cacahuete\",\"meal_id\":\"meal_20260420113202_b9ee4f\",\"created_at\":\"2026-04-20 11:32:02\",\"analyse_ia\":1,\"analyse_ia_model\":\"gemini-3-flash-preview\",\"analyse_ia_engine\":\"gemini\",\"analyse_ia_updated_at\":\"2026-05-07 11:36:32\",\"analyse_ia_error\":\"\",\"ingredients_standardises\":[\"nouilles de riz\",\"poulet\",\"cacahuètes\",\"oeufs\",\"sauce pad thai\",\"carottes\",\"champignons\",\"pousses de soja\",\"citron vert\",\"sauce de poisson\",\"pâte de tamarin\"],\"ingredients_manquants_probables\":[\"oeufs\",\"sauce de poisson\",\"pousses de soja\",\"citron vert\",\"pâte de tamarin\"],\"allergenes_finaux\":[\"arachide\",\"oeuf\",\"poisson\",\"soja\",\"gluten\",\"crustaces\"]}]', 1, '2026-04-20 10:09:02', '2026-05-07 10:36:32'),
(4, 1, 'dar louay', 'Office National Des Huiles, Rue du 13 Aout, Ezzouhour, Bach-Hamba, Délégation Ezzouhour, Tunis, Gouvernorat Tunis, 2052, Tunisie', 36.7941003, 10.1378487, 'assets/restaurant-signs/restaurant_20260428_122516_0ec9a21a.jpg', 'ENA NOSKON FI BARDO BAHDHA L KLEB NBI3 CHAWARMA HOT DOG', '+21698910180', '11:00-20:00', '[{\"meal_name\":\"chawarma\",\"ingredients\":\"viande dinde, tomate, salade grillée, oignons, harissa, choux, pain libanais\",\"quantity\":7,\"pricing_mode\":\"paid\",\"price\":50,\"regime_tags\":\"bio, halal\",\"allergen_disclosure_mode\":\"none\",\"allergens\":\"\",\"meal_id\":\"meal_20260428122653_9dd0f1\",\"created_at\":\"2026-04-28 12:26:53\",\"analyse_ia\":1,\"analyse_ia_model\":\"gemini-3-flash-preview\",\"analyse_ia_engine\":\"gemini\",\"analyse_ia_updated_at\":\"2026-05-07 10:23:23\",\"analyse_ia_error\":\"\",\"ingredients_standardises\":[\"viande de dinde\",\"tomate\",\"salade grillée\",\"oignons\",\"harissa\",\"choux\",\"pain libanais\",\"yaourt\",\"ail\",\"tahini\",\"épices\",\"huile végétale\"],\"ingredients_manquants_probables\":[\"yaourt (marinade)\",\"ail\",\"tahini\",\"épices\"],\"allergenes_finaux\":[\"gluten\",\"lactose\",\"sesame\"]},{\"meal_name\":\"plat tunisien\",\"ingredients\":\"salade fraiche, salade grillée, thon, oeuf, harissa, mayonnaise\",\"quantity\":10,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"halal\",\"allergen_disclosure_mode\":\"none\",\"allergens\":\"\",\"meal_id\":\"meal_20260505152344_adb8c9\",\"created_at\":\"2026-05-05 15:23:44\",\"analyse_ia\":1,\"analyse_ia_model\":\"gemini-3-flash-preview\",\"analyse_ia_engine\":\"gemini\",\"analyse_ia_updated_at\":\"2026-05-07 10:23:33\",\"analyse_ia_error\":\"\",\"ingredients_standardises\":[\"tomate\",\"concombre\",\"oignon\",\"poivron grillé\",\"thon\",\"oeuf\",\"harissa\",\"mayonnaise\",\"pomme de terre\",\"olives\",\"câpres\",\"huile d\'olive\",\"pain\"],\"ingredients_manquants_probables\":[\"pomme de terre\",\"olives\",\"câpres\",\"pain\",\"huile d\'olive\"],\"allergenes_finaux\":[\"poisson\",\"oeuf\",\"moutarde\",\"gluten\"]},{\"meal_name\":\"kafteji\",\"ingredients\":\"patate, oeufs, poivron vert, huile vegetale\",\"quantity\":6,\"pricing_mode\":\"paid\",\"price\":4,\"regime_tags\":\"halal, vegetarien\",\"allergen_disclosure_mode\":\"none\",\"allergens\":\"\",\"meal_id\":\"meal_20260507104253_f8881b\",\"created_at\":\"2026-05-07 10:42:53\",\"analyse_ia\":1,\"analyse_ia_model\":\"gemini-3-flash-preview\",\"analyse_ia_engine\":\"gemini\",\"analyse_ia_updated_at\":\"2026-05-07 10:42:59\",\"analyse_ia_error\":\"\",\"ingredients_standardises\":[\"patate\",\"oeuf\",\"poivron vert\",\"citrouille\",\"tomate\",\"ail\",\"huile vegetale\",\"harissa\",\"carvi\",\"sel\"],\"ingredients_manquants_probables\":[\"citrouille\",\"tomate\",\"ail\",\"harissa\",\"carvi\"],\"allergenes_finaux\":[\"oeuf\"]}]', 1, '2026-04-28 11:25:16', '2026-05-07 09:42:59'),
(5, 1, 'chez hamido', 'Lycée Khaznadar, Avenue de l\'Indépendance, Khaznadar, Délégation Le Bardo, Tunis, Gouvernorat Tunis, 2017, Tunisie', 36.8075667, 10.1246098, 'assets/restaurant-signs/restaurant_20260428_151324_2540233c.png', 'JE SUIS UN RESTOTOTOTOTOTOTOTTOTOTOTOTO', '+21698437272', '09:08-15:00', '[{\"meal_name\":\"cheese burger\",\"ingredients\":\"viande, laitue, tomate, ketchup, mayonnaise, fromage cheddar\",\"quantity\":15,\"pricing_mode\":\"paid\",\"price\":3,\"regime_tags\":\"equilibre, halal\",\"allergens\":\"\",\"meal_id\":\"meal_20260503202442_c3496a\",\"created_at\":\"2026-05-03 20:24:42\",\"analyse_ia\":1,\"analyse_ia_model\":\"gemini-3-flash-preview\",\"analyse_ia_engine\":\"gemini\",\"analyse_ia_updated_at\":\"2026-05-07 10:23:38\",\"analyse_ia_error\":\"\",\"ingredients_standardises\":[\"pain burger\",\"viande de boeuf\",\"fromage cheddar\",\"laitue\",\"tomate\",\"oignon\",\"cornichon\",\"ketchup\",\"mayonnaise\",\"moutarde\"],\"ingredients_manquants_probables\":[\"pain burger\",\"oignon\",\"cornichon\",\"moutarde\"],\"allergenes_finaux\":[\"gluten\",\"lactose\",\"oeuf\",\"moutarde\",\"sesame\",\"celeri\",\"sulfites\"],\"allergen_disclosure_mode\":\"none\"},{\"meal_name\":\"croque madame jambon\",\"ingredients\":\"pain de mie, oeuf, jambon, fromage mozza, mayonnaise\",\"quantity\":12,\"pricing_mode\":\"paid\",\"price\":2,\"regime_tags\":\"halal, budget\",\"allergen_disclosure_mode\":\"none\",\"allergens\":\"\",\"meal_id\":\"meal_20260503202841_130fc7\",\"created_at\":\"2026-05-03 20:28:41\",\"analyse_ia\":1,\"analyse_ia_model\":\"gemini-3-flash-preview\",\"analyse_ia_engine\":\"gemini\",\"analyse_ia_updated_at\":\"2026-05-07 10:41:28\",\"analyse_ia_error\":\"\",\"ingredients_standardises\":[\"pain de mie\",\"oeuf\",\"jambon\",\"fromage mozzarella\",\"mayonnaise\",\"beurre\",\"moutarde\"],\"ingredients_manquants_probables\":[\"beurre\",\"moutarde\"],\"allergenes_finaux\":[\"gluten\",\"lactose\",\"oeuf\",\"moutarde\"]}]', 1, '2026-04-28 14:13:24', '2026-05-07 09:41:28'),
(6, 1, 'sugar overload', 'La Marsa, Tunis, Gouvernorat Tunis, 2070, Tunisie', 36.8790882, 10.3276780, 'assets/restaurant-signs/restaurant_20260505_152835_f58fe7bd.jpg', 'BIENVENUE ICI TESTSTSTTSTSTSTSTSTSTTSTSTSTSSTST', '+21638902918', '10:00-22:00', '[{\"meal_name\":\"tarte aux fruits\",\"ingredients\":\"fruits\",\"quantity\":7,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"fruits\",\"allergen_disclosure_mode\":\"none\",\"allergens\":\"\",\"meal_id\":\"meal_20260507072209_922713\",\"created_at\":\"2026-05-07 07:22:09\",\"analyse_ia\":1,\"analyse_ia_model\":\"gemini-3-flash-preview\",\"analyse_ia_engine\":\"gemini\",\"analyse_ia_updated_at\":\"2026-05-07 10:24:30\",\"analyse_ia_error\":\"\",\"ingredients_standardises\":[\"farine de blé\",\"beurre\",\"sucre\",\"oeufs\",\"lait\",\"fruits\",\"vanille\",\"amidon de maïs\",\"sel\"],\"ingredients_manquants_probables\":[\"farine de blé\",\"beurre\",\"sucre\",\"oeufs\",\"lait\",\"vanille\",\"amidon de maïs\",\"sel\"],\"allergenes_finaux\":[\"gluten\",\"lactose\",\"oeuf\"]},{\"meal_name\":\"brownies\",\"ingredients\":\"chocolat\",\"quantity\":20,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"halal, vegetarien\",\"allergen_disclosure_mode\":\"none\",\"allergens\":\"\",\"meal_id\":\"meal_20260507104422_870f35\",\"created_at\":\"2026-05-07 10:44:22\",\"analyse_ia\":1,\"analyse_ia_model\":\"gemini-3-flash-preview\",\"analyse_ia_engine\":\"gemini\",\"analyse_ia_updated_at\":\"2026-05-07 10:44:28\",\"analyse_ia_error\":\"\",\"ingredients_standardises\":[\"chocolat\",\"beurre\",\"sucre\",\"oeufs\",\"farine de blé\",\"sel\",\"extrait de vanille\"],\"ingredients_manquants_probables\":[\"beurre\",\"sucre\",\"oeufs\",\"farine de blé\",\"sel\",\"extrait de vanille\"],\"allergenes_finaux\":[\"gluten\",\"lactose\",\"oeuf\",\"soja\"]},{\"meal_name\":\"kounefa\",\"ingredients\":\"fromage\",\"quantity\":10,\"pricing_mode\":\"free\",\"price\":0,\"regime_tags\":\"vegetarien\",\"allergen_disclosure_mode\":\"none\",\"allergens\":\"\",\"meal_id\":\"meal_20260507110758_650d14\",\"created_at\":\"2026-05-07 11:07:58\",\"analyse_ia\":1,\"analyse_ia_model\":\"gemini-3-flash-preview\",\"analyse_ia_engine\":\"gemini\",\"analyse_ia_updated_at\":\"2026-05-07 11:29:27\",\"analyse_ia_error\":\"\",\"ingredients_standardises\":[\"pâte kataifi\",\"beurre\",\"fromage\",\"sucre\",\"eau\",\"jus de citron\",\"eau de fleur d\'oranger\",\"pistaches\"],\"ingredients_manquants_probables\":[\"pâte kataifi\",\"beurre\",\"sucre\",\"pistaches\"],\"allergenes_finaux\":[\"gluten\",\"lactose\",\"fruits-a-coque\"]}]', 1, '2026-05-05 14:28:35', '2026-05-07 10:29:27');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','student','partner') NOT NULL DEFAULT 'student',
  `status` enum('active','inactive','banned') NOT NULL DEFAULT 'active',
  `referral_code` varchar(20) DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `lockout_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `role`, `status`, `referral_code`, `failed_login_attempts`, `lockout_until`, `created_at`, `updated_at`) VALUES
(1, 'test@esprit.tn', 'admin123', 'admin', 'active', NULL, 4, '2026-05-09 15:19:57', '2026-05-07 18:39:10', '2026-05-09 13:04:57'),
(2, 'zarrouky75@gmail.com', '$2y$10$m2n/tcDqEKPvcUHGGIYHJepjVwqELXllSI1.SQUmdsSTXbbsWKnL2', 'admin', 'active', 'F834E3E7', 0, NULL, '2026-05-07 21:29:14', '2026-05-08 07:57:48'),
(4, 'Zarrouk.Louay@esprit.tn', '$2y$10$javTlzIxjKfq8AAzqRZ54eeKMyx.WLbde620o3jpN/bpowwCCiBaW', 'student', 'active', 'CARE-4-0C88', 0, NULL, '2026-05-08 08:00:00', '2026-05-08 16:16:15'),
(5, 'contact@baguettedoree.tn', '$2y$10$yljBsoV6YLRd6qfzM8kXluEywmKefB0Qkgs9GZo9WtDe1MlcENSEi', 'partner', 'active', NULL, 0, NULL, '2026-05-08 08:19:31', '2026-05-08 08:19:31'),
(7, 'bensalahhadil21@gmail.com', '', 'admin', 'active', 'AEE2D34A', 0, NULL, '2026-05-09 13:12:43', '2026-05-09 13:26:23'),
(8, 'hadil.bensalah@esprit.tn', '$2y$10$KEDEPf1Np7RY0SoNm3yJouoL26k0Bep8jGxau9T5Wno8uuC9/XY8S', 'student', 'active', 'CARE-8-C43B', 0, NULL, '2026-05-09 13:29:46', '2026-05-09 13:29:46');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`id_categorie`),
  ADD UNIQUE KEY `uq_categorie_nom` (`nom`);

--
-- Index pour la table `categorie_offre`
--
ALTER TABLE `categorie_offre`
  ADD PRIMARY KEY (`id_categorie`);

--
-- Index pour la table `commande`
--
ALTER TABLE `commande`
  ADD PRIMARY KEY (`id_commande`),
  ADD KEY `idx_commande_user` (`id_user`),
  ADD KEY `idx_commande_statut` (`statut`),
  ADD KEY `idx_commande_date` (`date_commande`),
  ADD KEY `fk_commande_produit` (`id_produit`);

--
-- Index pour la table `commander`
--
ALTER TABLE `commander`
  ADD PRIMARY KEY (`id_commande`),
  ADD KEY `idx_commander_id_offre` (`id_offre`);

--
-- Index pour la table `commentaire`
--
ALTER TABLE `commentaire`
  ADD PRIMARY KEY (`id_commentaire`),
  ADD KEY `idx_commentaire_publication` (`id_publication`);

--
-- Index pour la table `commentaire_reaction`
--
ALTER TABLE `commentaire_reaction`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_comment_reaction` (`commentaire_id`,`auteur_type`,`auteur_id`);

--
-- Index pour la table `evenement`
--
ALTER TABLE `evenement`
  ADD PRIMARY KEY (`id_evenement`);

--
-- Index pour la table `matching_snapshot`
--
ALTER TABLE `matching_snapshot`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_snapshot_key` (`id_user`,`id_pref`,`price_mode`,`sort_by`,`safety_mode`),
  ADD KEY `idx_snapshot_source_version` (`source_version`),
  ADD KEY `idx_snapshot_pref` (`id_pref`),
  ADD KEY `idx_snapshot_user` (`id_user`);

--
-- Index pour la table `matching_source_version`
--
ALTER TABLE `matching_source_version`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `offre`
--
ALTER TABLE `offre`
  ADD PRIMARY KEY (`id_offre`),
  ADD KEY `idx_offre_categorie` (`id_categorie`);

--
-- Index pour la table `participation`
--
ALTER TABLE `participation`
  ADD PRIMARY KEY (`id_participation`),
  ADD KEY `fk_participation_evenement` (`evenement_id`),
  ADD KEY `fk_participation_etudiant` (`etudiant_id`);

--
-- Index pour la table `planning_collecte`
--
ALTER TABLE `planning_collecte`
  ADD PRIMARY KEY (`id_collecte`),
  ADD KEY `idx_collecte_restaurant` (`id_restaurant`),
  ADD KEY `idx_collecte_pref` (`id_pref`),
  ADD KEY `idx_collecte_user` (`id_user`),
  ADD KEY `idx_collecte_statut` (`statut`),
  ADD KEY `idx_collecte_heure` (`heure_souhaitee`);

--
-- Index pour la table `preference`
--
ALTER TABLE `preference`
  ADD PRIMARY KEY (`id_pref`),
  ADD KEY `fk_user_preference` (`id_user`);

--
-- Index pour la table `produit`
--
ALTER TABLE `produit`
  ADD PRIMARY KEY (`id_produit`),
  ADD KEY `idx_produit_categorie` (`id_categorie`),
  ADD KEY `idx_produit_actif` (`actif`);

--
-- Index pour la table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_id` (`user_id`);

--
-- Index pour la table `publication`
--
ALTER TABLE `publication`
  ADD PRIMARY KEY (`id_publication`);

--
-- Index pour la table `publication_reaction`
--
ALTER TABLE `publication_reaction`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reaction` (`publication_id`,`auteur_type`,`auteur_id`);

--
-- Index pour la table `regime_option`
--
ALTER TABLE `regime_option`
  ADD PRIMARY KEY (`id_option`),
  ADD UNIQUE KEY `option_value` (`option_value`),
  ADD UNIQUE KEY `option_label` (`option_label`);

--
-- Index pour la table `restaurant`
--
ALTER TABLE `restaurant`
  ADD PRIMARY KEY (`id_restaurant`),
  ADD KEY `idx_owner` (`id_owner`),
  ADD KEY `idx_nom` (`nom`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD UNIQUE KEY `uq_users_referral_code` (`referral_code`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `id_categorie` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `categorie_offre`
--
ALTER TABLE `categorie_offre`
  MODIFY `id_categorie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT pour la table `commande`
--
ALTER TABLE `commande`
  MODIFY `id_commande` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `commander`
--
ALTER TABLE `commander`
  MODIFY `id_commande` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `commentaire`
--
ALTER TABLE `commentaire`
  MODIFY `id_commentaire` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commentaire_reaction`
--
ALTER TABLE `commentaire_reaction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `evenement`
--
ALTER TABLE `evenement`
  MODIFY `id_evenement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `matching_snapshot`
--
ALTER TABLE `matching_snapshot`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `offre`
--
ALTER TABLE `offre`
  MODIFY `id_offre` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT pour la table `participation`
--
ALTER TABLE `participation`
  MODIFY `id_participation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `planning_collecte`
--
ALTER TABLE `planning_collecte`
  MODIFY `id_collecte` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pour la table `preference`
--
ALTER TABLE `preference`
  MODIFY `id_pref` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT pour la table `produit`
--
ALTER TABLE `produit`
  MODIFY `id_produit` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `publication`
--
ALTER TABLE `publication`
  MODIFY `id_publication` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `publication_reaction`
--
ALTER TABLE `publication_reaction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `regime_option`
--
ALTER TABLE `regime_option`
  MODIFY `id_option` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `restaurant`
--
ALTER TABLE `restaurant`
  MODIFY `id_restaurant` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `commande`
--
ALTER TABLE `commande`
  ADD CONSTRAINT `fk_commande_produit` FOREIGN KEY (`id_produit`) REFERENCES `produit` (`id_produit`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `commander`
--
ALTER TABLE `commander`
  ADD CONSTRAINT `commander_ibfk_1` FOREIGN KEY (`id_offre`) REFERENCES `offre` (`id_offre`) ON DELETE CASCADE;

--
-- Contraintes pour la table `commentaire`
--
ALTER TABLE `commentaire`
  ADD CONSTRAINT `commentaire_ibfk_1` FOREIGN KEY (`id_publication`) REFERENCES `publication` (`id_publication`) ON DELETE CASCADE;

--
-- Contraintes pour la table `offre`
--
ALTER TABLE `offre`
  ADD CONSTRAINT `fk_offre_categorie` FOREIGN KEY (`id_categorie`) REFERENCES `categorie_offre` (`id_categorie`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `participation`
--
ALTER TABLE `participation`
  ADD CONSTRAINT `fk_participation_etudiant` FOREIGN KEY (`etudiant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_participation_evenement` FOREIGN KEY (`evenement_id`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `planning_collecte`
--
ALTER TABLE `planning_collecte`
  ADD CONSTRAINT `fk_collecte_preference` FOREIGN KEY (`id_pref`) REFERENCES `preference` (`id_pref`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_collecte_restaurant` FOREIGN KEY (`id_restaurant`) REFERENCES `restaurant` (`id_restaurant`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_collecte_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `preference`
--
ALTER TABLE `preference`
  ADD CONSTRAINT `fk_user_preference` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `produit`
--
ALTER TABLE `produit`
  ADD CONSTRAINT `fk_produit_categorie` FOREIGN KEY (`id_categorie`) REFERENCES `categorie` (`id_categorie`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
