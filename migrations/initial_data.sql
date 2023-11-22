SET NAMES utf8;

SET time_zone = '+00:00';

SET foreign_key_checks = 0;

SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

INSERT INTO `content` (`id`, `page_id`, `heading`, `area`, `position`, `type`) VALUES
  (1, 1, 'Úspěšně jste nainstalovali SRS. Gratulujeme!', 'main', 1, 'text_content'),
  (2, 1, 'Pořadatel', 'sidebar', 1, 'organizer_content');

INSERT INTO `organizer_content` (`id`, `organizer`) VALUES
  (2, 'Akci pořádá Junák - český skaut, z. s.');

INSERT INTO `page` (`id`, `name`, `slug`, `position`, `public`) VALUES
  (1, 'Homepage', '/', 1, 1);

INSERT INTO `page_role` (`page_id`, `role_id`) VALUES
  (1, 1),
  (1, 2),
  (1, 3),
  (1, 4),
  (1, 5),
  (1, 6),
  (1, 7),
  (1, 8);

INSERT INTO `permission` (`id`, `resource_id`, `name`) VALUES
  (1, 1, 'access'),
  (2, 3, 'manage'),
  (3, 2, 'manage'),
  (4, 5, 'manage'),
  (5, 4, 'access'),
  (6, 4, 'manage_own_programs'),
  (7, 4, 'manage_all_programs'),
  (8, 4, 'manage_schedule'),
  (9, 4, 'manage_rooms'),
  (10, 4, 'manage_categories'),
  (11, 4, 'choose_programs'),
  (12, 6, 'manage'),
  (13, 7, 'manage');

INSERT INTO `resource` (`id`, `name`) VALUES
  (3, 'acl'),
  (1, 'admin'),
  (2, 'cms'),
  (5, 'configuration'),
  (7, 'mailing'),
  (4, 'program'),
  (6, 'users');

INSERT INTO `role` (`id`, `name`, `system_name`, `system`, `registerable`, `approved_after_registration`, `registerable_from`, `registerable_to`, `capacity`, `fee`, `display_arrival_departure`, `synced_with_skaut_is`, `redirect_after_login`) VALUES
  (1, 'guest', 'guest', 1, 0, 0, NULL, NULL, NULL, 0, 0, 0, NULL),
  (2, 'Nepřihlášený', 'nonregistered', 1, 0, 0, NULL, NULL, NULL, 0, 0, 0, NULL),
  (3, 'Neschválený', 'unapproved', 1, 0, 0, NULL, NULL, NULL, 0, 0, 0, NULL),
  (4, 'Účastník', 'attendee', 1, 1, 1, NULL, NULL, NULL, 0, 0, 1, NULL),
  (5, 'Servis tým', 'service_team', 1, 1, 0, NULL, NULL, NULL, 0, 0, 1, NULL),
  (6, 'Lektor', 'lector', 1, 1, 0, NULL, NULL, NULL, 0, 0, 1, NULL),
  (7, 'Organizátor', 'organizer', 1, 1, 0, NULL, NULL, NULL, 0, 0, 1, NULL),
  (8, 'Administrátor', 'admin', 1, 0, 0, NULL, NULL, NULL, 0, 0, 1, NULL);

INSERT INTO `role_permission` (`role_id`, `permission_id`) VALUES
  (4, 11),
  (5, 1),
  (5, 5),
  (6, 1),
  (6, 5),
  (6, 6),
  (7, 1),
  (7, 2),
  (7, 3),
  (7, 4),
  (7, 5),
  (7, 7),
  (7, 8),
  (7, 9),
  (7, 10),
  (7, 11),
  (7, 12),
  (7, 13),
  (8, 1),
  (8, 2),
  (8, 3),
  (8, 4),
  (8, 5),
  (8, 7),
  (8, 8),
  (8, 9),
  (8, 10),
  (8, 11),
  (8, 12),
  (8, 13);

INSERT INTO `settings` (`item`, `value`) VALUES
  ('account_number', ''),
  ('accountant', ''),
  ('admin_created', '0'),
  ('application_agreement', 'Má účast na této akci je součástí činnosti Junáka - českého skauta a jeho jednotek, a tak beru na vědomí, že zpracování mých osobních údajů pro potřeby této akce se řídí pravidly, poučením a souhlasy danými v rámci přihlášky člena do organizace Junák - český skaut (případně pokud nejsem členem, tak přihláškou nečlena na akci). Podrobné znění je vždy dostupné na www.skaut.cz/osobniudaje. Zpracování osobních údajů a nakládání s nimi na této akci probíhá v souladu s Nařízením Evropského parlamentu a Rady 2016/679 (tzv. GDPR) a zákonem 101/2000 Sb., o ochraně osobních údajů, ve znění pozdějších předpisů. Společným správcem osobních údajů je Junák - český skaut, z. s. (hlavní spolek) a případné další pořádající organizační jednotky (pobočný spolek uvedený v kontaktech na webu této akce).'),
  ('company', ''),
  ('display_users_roles', '1'),
  ('edit_registration_to', CURDATE()),
  ('footer', CONCAT('© ', YEAR(CURDATE()), ' SRS')),
  ('ico', ''),
  ('is_allowed_add_block', '1'),
  ('is_allowed_modify_schedule', '1'),
  ('is_allowed_register_programs', '0'),
  ('is_allowed_register_programs_before_payment', '0'),
  ('logo', 'logo.png'),
  ('place_description', NULL),
  ('print_location', ''),
  ('redirect_after_login', '/'),
  ('register_programs_from', NULL),
  ('register_programs_to', NULL),
  ('seminar_email', 'srs@skaut.cz'),
  ('seminar_from_date', CURDATE() + 1),
  ('seminar_name', 'Název semináře'),
  ('seminar_to_date', CURDATE() + 2),
  ('skautis_event_id', NULL),
  ('skautis_event_name', NULL),
  ('variable_symbol_code', '');

INSERT INTO `text_content` (`id`, `text`) VALUES
  (1, '<p>Obsah této stránky můžete změnit v administraci v sekci Web.</p>');
