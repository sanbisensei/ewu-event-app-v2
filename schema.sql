
CREATE DATABASE IF NOT EXISTS ewu_event CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ewu_event;



CREATE TABLE Managers (
    manager_id VARCHAR(5) PRIMARY KEY,
    manager_pass VARCHAR(60) NOT NULL,
    manager_name VARCHAR(100),
    manager_phone VARCHAR(20),
    manager_email VARCHAR(100)
);

CREATE TABLE Venues (
    venue_id VARCHAR(5) PRIMARY KEY,
    venue_name VARCHAR(100),
    venue_capacity INT,
    manager_id VARCHAR(5),
    venue_cost DECIMAL(12,2),
    house VARCHAR(100),
    road VARCHAR(100),
    city VARCHAR(100),
    CONSTRAINT fk_venues_manager FOREIGN KEY (manager_id) REFERENCES Managers(manager_id) ON DELETE SET NULL
);

CREATE TABLE Meals (
    meal_id VARCHAR(5) PRIMARY KEY,
    meal_name VARCHAR(100),
    meal_type VARCHAR(50),
    meal_cost DECIMAL(12,2),
    catering_company VARCHAR(100)
);

CREATE TABLE Events (
    event_id VARCHAR(5) PRIMARY KEY,
    event_name VARCHAR(100),
    event_type ENUM('ESPORTS','PRESENTATION','PRIZE GIVING','MEETING','SEMINAR','WORKSHOP','ENTERTAINMENT') DEFAULT 'MEETING',
    event_date DATE,
    venue_id VARCHAR(5),
    meal_id VARCHAR(5),
    guest_count INT,
    ticket_cost DECIMAL(12,2),
    CONSTRAINT fk_events_venue FOREIGN KEY (venue_id) REFERENCES Venues(venue_id) ON DELETE SET NULL,
    CONSTRAINT fk_events_meal FOREIGN KEY (meal_id) REFERENCES Meals(meal_id) ON DELETE SET NULL,
    CONSTRAINT uq_events_booking UNIQUE (venue_id, event_date, event_type)
);

CREATE TABLE Customers (
    customer_id VARCHAR(5) PRIMARY KEY,
    customer_pass VARCHAR(60) NOT NULL,
    customer_name VARCHAR(100),
    customer_address VARCHAR(200),
    customer_contact VARCHAR(20)
);

CREATE TABLE Bookings (
    booking_id VARCHAR(5) PRIMARY KEY,
    booking_date DATE,
    event_id VARCHAR(5),
    customer_id VARCHAR(5),
    total_cost DECIMAL(12,2),
    CONSTRAINT fk_bookings_event FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_customer FOREIGN KEY (customer_id) REFERENCES Customers(customer_id) ON DELETE CASCADE
);

CREATE TABLE Guests (
    guest_id VARCHAR(5) PRIMARY KEY,
    guest_name VARCHAR(100),
    guest_contact VARCHAR(20),
    event_id VARCHAR(5),
    customer_id VARCHAR(5),
    CONSTRAINT fk_guests_event FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE,
    CONSTRAINT fk_guests_customer FOREIGN KEY (customer_id) REFERENCES Customers(customer_id) ON DELETE CASCADE
);

CREATE TABLE Sponsors (
    sponsor_id VARCHAR(5) PRIMARY KEY,
    sponsor_address VARCHAR(200),
    sponsor_funding DECIMAL(12,2),
    event_id VARCHAR(5),
    CONSTRAINT fk_sponsor_event FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE
);

CREATE TABLE Cashflow (
    payment_id VARCHAR(5) PRIMARY KEY,
    customer_id VARCHAR(5),
    food_cost DECIMAL(12,2),
    venue_cost DECIMAL(12,2),
    ticket_earning DECIMAL(12,2),
    sponsor_funding DECIMAL(12,2),
    payment_method VARCHAR(50),
    CONSTRAINT fk_cashflow_customer FOREIGN KEY (customer_id) REFERENCES Customers(customer_id) ON DELETE CASCADE
);

CREATE TABLE Feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id VARCHAR(5),
    customer_id VARCHAR(5),
    recommendation VARCHAR(100),
    review VARCHAR(500),
    rating INT,
    CONSTRAINT fk_feedback_event FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_customer FOREIGN KEY (customer_id) REFERENCES Customers(customer_id) ON DELETE CASCADE
);

CREATE TABLE Logistics (
    venue_id VARCHAR(5),
    object_type VARCHAR(50),
    quantity INT,
    status VARCHAR(50),
    PRIMARY KEY (venue_id, object_type),
    CONSTRAINT fk_logistics_venue FOREIGN KEY (venue_id) REFERENCES Venues(venue_id) ON DELETE CASCADE
);

-- demo seed
INSERT INTO Managers (manager_id, manager_pass, manager_name, manager_phone, manager_email) VALUES ('M001','12345','Admin One','01700000001','admin1@ewu.edu');

INSERT INTO Customers (customer_id, customer_pass, customer_name, customer_address, customer_contact) VALUES
('C001','12345','Alice Rahman','Dhanmondi, Dhaka','01888888888'),
('C002','12345','Hasib Khan','Rampura, Dhaka','01777777777');

-- INSERT INTO Venues (venue_id, venue_name, venue_capacity, manager_id, venue_cost, house, road, city) VALUES ('V001','EWU Auditorium',500,'M001',20000.00,'House 23','Road 5','Dhaka');

-- INSERT INTO Meals (meal_id, meal_name, meal_type, meal_cost, catering_company) VALUES ('ME01','Biryani Package','DINNER',350.00,'Star Catering');

-- INSERT INTO Events (event_id, event_name, event_type, event_date, venue_id, meal_id, guest_count, ticket_cost) VALUES ('EV01','Freshers 2025','SEMINAR','2025-12-15','V001','ME01',400,500.00);

-- INSERT INTO Guests (guest_id, guest_name, guest_contact, event_id, customer_id) VALUES ('G001','Tanvir Hasan','01712345678','EV01','C001');

-- INSERT INTO Sponsors (sponsor_id, sponsor_address, sponsor_funding, event_id) VALUES ('S001','Banani Office',100000.00,'EV01');

-- INSERT INTO Cashflow (payment_id, customer_id, food_cost, venue_cost, ticket_earning, sponsor_funding, payment_method) VALUES ('P001','C001',350.00*400,20000.00,500.00*400,100000.00,'Bkash');

-- INSERT INTO Bookings (booking_id, booking_date, event_id, customer_id, total_cost) VALUES ('B001',CURDATE(),'EV01','C001',500.00*1);
