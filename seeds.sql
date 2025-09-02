INSERT INTO Venues (venue_id, venue_name, venue_capacity, manager_id, venue_cost, house, road, city) VALUES
('V011','Blue Moon Hall',600,'M001',25000.00,'House 21','Road 3','Dhaka'),
('V012','Golden Crown Center',450,'M001',20000.00,'House 12','Road 8','Dhaka'),
('V013','Emerald Pavilion',700,'M001',30000.00,'House 33','Road 5','Dhaka'),
('V014','Diamond Convention',1000,'M001',40000.00,'House 40','Road 9','Dhaka'),
('V015','Rose Valley Hall',300,'M001',15000.00,'House 7','Road 11','Dhaka'),
('V016','Crystal Dome',800,'M001',35000.00,'House 19','Road 2','Dhaka'),
('V017','Star Light Auditorium',500,'M001',28000.00,'House 25','Road 6','Dhaka'),
('V018','Magnolia Garden',200,'M001',10000.00,'House 9','Road 12','Dhaka'),
('V019','Orchid Center',350,'M001',18000.00,'House 15','Road 4','Dhaka'),
('V020','Sapphire Plaza',900,'M001',45000.00,'House 50','Road 10','Dhaka');


INSERT INTO Meals (meal_id, meal_name, meal_type, meal_cost, catering_company) VALUES
('ME011','Classic Buffet','BUFFET',550.00,'Royal Caterers'),
('ME012','Deluxe Lunch','BUFFET',750.00,'Foodie Palace'),
('ME013','Mini Snacks','SNACK',180.00,'QuickBites Ltd.'),
('ME014','Sweet Corner','SNACK',300.00,'Cake World'),
('ME015','Vegetarian Buffet','BUFFET',500.00,'Green Leaf'),
('ME016','Fish Feast','BUFFET',850.00,'Seafood Hub'),
('ME017','Chicken Combo','BUFFET',650.00,'Grill Masters'),
('ME018','Tea Time','SNACK',120.00,'Chai Adda'),
('ME019','Fruit Mix','SNACK',250.00,'Healthy Treats'),
('ME020','Dessert Deluxe','SNACK',400.00,'Sugar Bliss');


INSERT INTO Events (event_id, event_name, event_type, event_date, venue_id, meal_id, guest_count, ticket_cost) VALUES
('E011','Robotics Expo','WORKSHOP','2025-10-10','V011','ME011',150,200.00),
('E012','Cultural Night','ENTERTAINMENT','2025-10-12','V012','ME012',300,500.00),
('E013','Business Summit','MEETING','2025-10-15','V013','ME013',250,300.00),
('E014','Innovation Awards','PRIZE GIVING','2025-10-18','V014','ME014',400,400.00),
('E015','Science Fair','PRESENTATION','2025-10-20','V015','ME015',200,150.00),
('E016','Gaming Marathon','ESPORTS','2025-10-22','V016','ME016',350,350.00),
('E017','Entrepreneur Talk','SEMINAR','2025-10-25','V017','ME017',180,220.00),
('E018','Film Screening','ENTERTAINMENT','2025-10-28','V018','ME018',220,100.00),
('E019','Startup Showcase','PRESENTATION','2025-11-01','V019','ME019',120,250.00),
('E020','Poetry Fest','ENTERTAINMENT','2025-11-05','V020','ME020',260,180.00);


INSERT INTO Bookings (booking_id, booking_date, event_id, customer_id, total_cost) VALUES
('B011','2025-09-11','E011','C001',30000.00),
('B012','2025-09-12','E012','C002',150000.00),
('B013','2025-09-13','E013','C001',75000.00),
('B014','2025-09-14','E014','C002',160000.00),
('B015','2025-09-15','E015','C001',30000.00),
('B016','2025-09-16','E016','C002',122500.00),
('B017','2025-09-17','E017','C001',39600.00),
('B018','2025-09-18','E018','C002',22000.00),
('B019','2025-09-19','E019','C001',30000.00),
('B020','2025-09-20','E020','C002',46800.00);


INSERT INTO Guests (guest_id, guest_name, guest_contact, event_id, customer_id) VALUES
('G011','Mahfuz Rahman','01711111111','E011','C001'),
('G012','Farzana Sultana','01822222222','E012','C002'),
('G013','Rakibul Islam','01933333333','E013','C001'),
('G014','Shamima Akter','01644444444','E014','C002'),
('G015','Towhid Hasan','01555555555','E015','C001'),
('G016','Sadia Jahan','01766666666','E016','C002'),
('G017','Mehedi Hasan','01877777777','E017','C001'),
('G018','Samira Alam','01988888888','E018','C002'),
('G019','Ashiqur Rahman','01699999999','E019','C001'),
('G020','Fahima Chowdhury','01500000001','E020','C002');


INSERT INTO Feedback (event_id, customer_id, recommendation, review, rating) VALUES
('E011','C001','Yes','Robotics expo was inspiring!',5),
('E012','C002','Yes','Cultural night was full of energy.',4),
('E013','C001','No','Business summit was too long.',3),
('E014','C002','Yes','Loved the innovation awards!',5),
('E015','C001','Yes','Science fair had great ideas.',4),
('E016','C002','Yes','Gaming marathon was thrilling.',5),
('E017','C001','No','Entrepreneur talk was boring.',2),
('E018','C002','Yes','Film screening was fun.',4),
('E019','C001','Yes','Startup showcase was motivating.',5),
('E020','C002','No','Poetry fest felt disorganized.',3);


INSERT INTO Logistics (venue_id, object_type, quantity, status) VALUES
('V011','Stage Lights',10,'Available'),
('V012','Microphones',8,'In Use'),
('V013','Chairs',200,'Available'),
('V014','Tables',50,'In Use'),
('V015','Sound System',3,'Available'),
('V016','LED Screens',5,'Available'),
('V017','Fans',20,'In Use'),
('V018','AC Units',4,'Available'),
('V019','Projector',2,'Available'),
('V020','Podium',1,'In Use');


INSERT INTO Cashflow (payment_id, customer_id, food_cost, venue_cost, ticket_earning, sponsor_funding, payment_method) VALUES
('P011','C001',8000.00,25000.00,30000.00,70000.00,'Bkash'),
('P012','C002',12000.00,20000.00,150000.00,120000.00,'Cash'),
('P013','C001',10000.00,30000.00,75000.00,50000.00,'Card'),
('P014','C002',20000.00,40000.00,160000.00,140000.00,'Bkash'),
('P015','C001',7000.00,15000.00,30000.00,60000.00,'Cash'),
('P016','C002',15000.00,35000.00,122500.00,130000.00,'Card'),
('P017','C001',9000.00,20000.00,39600.00,200000.00,'Bkash'),
('P018','C002',4000.00,10000.00,22000.00,45000.00,'Cash'),
('P019','C001',6000.00,12000.00,30000.00,30000.00,'Nagad'),
('P020','C002',8000.00,18000.00,46800.00,80000.00,'Bkash');


INSERT INTO Sponsors (sponsor_id, sponsor_address, sponsor_funding, event_id) VALUES
('S011','Gulshan, Dhaka',70000.00,'E011'),
('S012','Banani, Dhaka',120000.00,'E012'),
('S013','Mirpur, Dhaka',50000.00,'E013'),
('S014','Uttara, Dhaka',140000.00,'E014'),
('S015','Dhanmondi, Dhaka',60000.00,'E015'),
('S016','Motijheel, Dhaka',130000.00,'E016'),
('S017','Bashundhara, Dhaka',200000.00,'E017'),
('S018','Farmgate, Dhaka',45000.00,'E018'),
('S019','Shyamoli, Dhaka',30000.00,'E019'),
('S020','Paltan, Dhaka',80000.00,'E020');