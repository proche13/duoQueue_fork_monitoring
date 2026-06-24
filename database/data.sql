-- Inserts for the tables (Test data)
INSERT INTO users (first_name, last_name, email, password, is_admin, is_banned) 
VALUES
('John', 'Doe', 'jDizzle@gmail.com', 'password123', false, false),
('Jane', 'Smith', 'janesmail@gmail.com', 'janespassword', false, false),
('Alex', 'Johnson', 'alex.j@gmail.com', 'pass456', false, false),
('Sarah', 'Williams', 'sarah.w@gmail.com', 'pass789', false, false),
('Mike', 'Brown', 'mike.b@gmail.com', 'pass101', false, false),
('Emma', 'Davis', 'emma.d@gmail.com', 'pass202', false, false),
('Chris', 'Miller', 'chris.m@gmail.com', 'pass303', false, false),
('Lisa', 'Wilson', 'lisa.w@gmail.com', 'pass404', false, false),
('Tom', 'Moore', 'tom.m@gmail.com', 'pass505', false, false),
('Jessica', 'Taylor', 'jessica.t@gmail.com', 'pass606', false, false),
('David', 'Anderson', 'david.a@gmail.com', 'pass707', false, false),
('Amy', 'Thomas', 'amy.t@gmail.com', 'pass808', false, false),
('Ryan', 'Jackson', 'ryan.j@gmail.com', 'pass909', false, false),
('Laura', 'White', 'laura.w@gmail.com', 'pass010', false, false),
('Kevin', 'Harris', 'kevin.h@gmail.com', 'pass111', false, false),
('Sofia', 'Martin', 'sofia.m@gmail.com', 'pass212', false, false),
('James', 'Thompson', 'james.t@gmail.com', 'pass313', false, false),
('Rachel', 'Garcia', 'rachel.g@gmail.com', 'pass414', false, false),
('Daniel', 'Martinez', 'daniel.m@gmail.com', 'pass515', false, false),
('Nicole', 'Robinson', 'nicole.r@gmail.com', 'pass616', false, false),
('Brandon', 'Clark', 'brandon.c@gmail.com', 'pass717', false, false),
('Megan', 'Rodriguez', 'megan.r@gmail.com', 'pass818', false, false),
('Tyler', 'Lewis', 'tyler.l@gmail.com', 'pass919', false, false),
('Hannah', 'Lee', 'hannah.l@gmail.com', 'pass020', false, false),
('Joshua', 'Walker', 'joshua.w@gmail.com', 'pass121', false, false),
('Olivia', 'Hall', 'olivia.h@gmail.com', 'pass222', false, false),
('Matthew', 'Allen', 'matthew.a@gmail.com', 'pass323', false, false),
('Ava', 'Young', 'ava.y@gmail.com', 'pass424', false, false),
('Andrew', 'Hernandez', 'andrew.h@gmail.com', 'pass525', false, false),
('Isabella', 'King', 'isabella.k@gmail.com', 'pass626', false, false),
('Christopher', 'Wright', 'christopher.w@gmail.com', 'pass727', false, false),
('Sophia', 'Lopez', 'sophia.l@gmail.com', 'pass828', false, false),
('Admin', 'User', 'admin@duoqueue.com', '$2y$12$C0oL9lgczJe17riKKm2zfeuPVtgwnicnQDAZXJxErAK09BDj20Sfi', true, false)
ON DUPLICATE KEY UPDATE
email=email;

INSERT INTO user_profiles (user_id, location, profile_photo, date_of_birth, gender, seeking, about_me, smoker, drinker)
VALUES
(1, 'Gorey', '', '2000-01-01', 'Male', 'Female', 'Lorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.', false, true),
(2, 'Feakle', '', '1999-12-3', 'Female', 'Female', 'Lorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.', true, false),
(3, 'Dublin', '', '1998-05-15', 'Male', 'Female', 'Passionate gamer looking for my duo! I love competitive games and strategy RPGs. Always up for new gaming friends and adventures in the digital world.', false, false),
(4, 'Cork', '', '2001-08-22', 'Female', 'Male', 'Casual player who loves cozy games and story-driven adventures. Looking for someone chill to game with and maybe grab a drink after.', false, true),
(5, 'Limerick', '', '1997-03-10', 'Male', 'Male', 'Hardcore FPS player. Competitive, dedicated, and always grinding. Looking for a serious gaming partner who can keep up with my skill level.', true, true),
(6, 'Waterford', '', '2000-11-07', 'Female', 'Female', 'Indie game enthusiast and speedrunner. Love challenging myself and discovering hidden gems. Would love to find a fellow speedrunner!', false, false),
(7, 'Galway', '', '1996-07-19', 'Male', 'Female', 'Retro gaming collector and speedrunner. Classic games are my passion. If you appreciate good gameplay and nostalgia, hit me up!', false, true),
(8, 'Belfast', '', '2002-01-30', 'Female', 'Other', 'MMO addict looking for raid buddies! I play daily and am looking for a committed teammate. Voice chat essential!', false, false),
(9, 'Derry', '', '1999-09-14', 'Male', 'Other', 'Open-minded gamer interested in all genres. Mostly play at night. Looking for chill people to have fun with, no pressure gaming.', true, false),
(10, 'Sligo', '', '2000-04-28', 'Female', 'Female', 'Overwatch and Valorant player. Competitive but fun-loving. Would love to find teammates who are passionate about improving together.', false, true),
(11, 'Droichead Átha', '', '1998-12-05', 'Male', 'Female', 'Story-driven game lover. I value good narratives and character development. Looking for someone to discuss games philosophically with!', false, false),
(12, 'Kilkenny', '', '2001-06-17', 'Female', 'Male', 'Casual mobile and Switch player. Looking for laid-back gaming sessions. No toxicity, just good vibes and fun times!', true, true),
(13, 'Athenry', '', '1997-02-14', 'Male', 'Female', 'CS:GO and Dota2 enthusiast. Competitive rank player looking for serious teammates. Need good communication and dedication!', false, true),
(14, 'Ennis', '', '2000-07-19', 'Female', 'Male', 'RPG and jRPG games are my jam. Currently replaying Final Fantasy. Looking for someone to discuss game lore with!', true, false),
(15, 'Tralee', '', '1996-11-23', 'Male', 'Other', 'Simulation game lover. Cities Skyline, Planet Coaster, and Stardew Valley enthusiast. Want gaming partner for co-op games.', false, false),
(16, 'Malahide', '', '1999-04-08', 'Female', 'Female', 'Minecraft and building game addict. Love creative projects and survival challenges. Looking for someone to build an empire with!', false, true),
(17, 'Dundalk', '', '1998-09-30', 'Male', 'Male', 'Battle royale specialist. Warzone and Apex Legends player. Looking for squad mates to dominate with!', true, true),
(18, 'Letterkenny', '', '2001-03-12', 'Female', 'Other', 'Horror game fan. Love jump scares and atmospheric experiences. Looking for someone brave to play horror games with!', false, false),
(19, 'Navan', '', '1997-08-06', 'Male', 'Female', 'Rhythm game expert. Osu and Dance Dance Revolution player. Always looking for competitive challenges!', false, false),
(20, 'Tullamore', '', '2000-12-17', 'Female', 'Male', 'Adventure seeker in gaming! Uncharted, Tomb Raider fan. Looking for someone to join me on epic gaming adventures!', true, true),
(21, 'Dungarvan', '', '1995-05-21', 'Male', 'Female', 'Board game to digital conversion enthusiast. Into strategy and turn-based games. Want someone for long gaming sessions!', false, true),
(22, 'Youghal', '', '2002-01-09', 'Female', 'Female', 'Casual cozy game player. Animal Crossing and Stardew Valley addict. Looking for relaxed gaming friend!', false, false),
(23, 'Bray', '', '1998-10-14', 'Male', 'Other', 'Souls-like game veteran. Dark Souls, Elden Ring, Bloodborne. Looking for challenging coop partner!', true, false),
(24, 'Wicklow', '', '2001-06-26', 'Female', 'Male', 'JRPG speedrunner and collector. Love all Final Fantasy games. Want to share gaming passion with someone special!', false, true),
(25, 'Greystones', '', '1996-09-11', 'Male', 'Female', 'FPS tournament player. Looking for serious team to compete in tournaments with. Need dedication!', false, false),
(26, 'Carlow', '', '1999-11-03', 'Female', 'Other', 'Puzzle and mystery game lover. Into story mysteries and brain teasers. Want someone to solve puzzles together!', true, true),
(27, 'Athy', '', '1997-04-18', 'Male', 'Male', 'VR gaming enthusiast. Beat Saber and Half-Life Alyx fan. Looking for someone to explore VR worlds with!', false, true),
(28, 'Naas', '', '2000-02-27', 'Female', 'Female', 'Sports game and FIFA player. Competitive player looking for squad battles partner!', false, false),
(29, 'Kildare', '', '1998-07-15', 'Male', 'Other', 'Roguelike game specialist. Hades, Dead Cells, Binding of Isaac. Love challenging runs and speedruns!', true, false),
(30, 'Celbridge', '', '2001-09-29', 'Female', 'Male', 'Survival game enthusiast. ARK and DayZ player. Looking for someone to survive the apocalypse with!', false, true),
(31, 'Clondalkin', '', '1996-03-22', 'Male', 'Female', 'Multiplayer fighting game buff. Street Fighter and Tekken player. Want to practice and improve with someone!', true, true),
(32, 'Ballymun', '', '1999-12-08', 'Female', 'Other', 'All-rounder gamer. Play everything! Looking for someone down to play any game together!', false, false),
(33, 'Dublin', '', '1990-01-01', 'Other', 'Other', 'System Administrator for DuoQueue. Managing the platform and ensuring smooth gaming experiences for all users.', false, false)
ON DUPLICATE KEY UPDATE
user_id=user_id;

INSERT INTO available_games (game_name) VALUES
('League of Legends'),
('Battlefield 6'),
('Call of Duty'),
('Plants vs Zombies'),
('Deadlock'),
('Fortnite'),
('Valorant'),
('Minecraft'),
('Stardew Valley'),
('Elden Ring')
ON DUPLICATE KEY UPDATE
game_name=game_name;

INSERT INTO available_platforms (platform_name) VALUES
('PC'),
('PlayStation'),
('Xbox'),
('Nintendo Switch'),
('Mobile'),
('VR')
ON DUPLICATE KEY UPDATE
platform_name=platform_name;