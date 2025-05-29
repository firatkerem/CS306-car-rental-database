/*─────────────────────────────────────────────────────────────*
  CLEAN RENT-A-CAR DATABASE
  – 10 entities  ·  6 relation tables  ·  4 triggers  ·  4 procs
 *─────────────────────────────────────────────────────────────*/

-- Charset ayarları
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET CHARACTER SET utf8mb4;

DROP DATABASE IF EXISTS car_rental_db;
CREATE DATABASE car_rental_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE car_rental_db;

/*==============================================================*/
/* 1  ENTITY TABLES                                             */
/*==============================================================*/

CREATE TABLE Customer (
    cus_id        INT AUTO_INCREMENT,
    full_name     VARCHAR(100)  NOT NULL,
    phone         VARCHAR(20),
    licence_num   VARCHAR(20)   UNIQUE,
    PRIMARY KEY (cus_id)
);

CREATE TABLE Branch (
    branch_id     INT AUTO_INCREMENT,
    location      VARCHAR(50),
    phone         VARCHAR(20),
    PRIMARY KEY (branch_id)
);

CREATE TABLE Employee (
    emp_id        INT AUTO_INCREMENT,
    full_name     VARCHAR(100)  NOT NULL,
    phone         VARCHAR(20),
    position      VARCHAR(50),
    salary        DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (emp_id)
);

CREATE TABLE Car (
    car_id        INT AUTO_INCREMENT,
    plate_number  VARCHAR(25)   UNIQUE,
    brand         VARCHAR(25),
    model_year    INT,
    PRIMARY KEY (car_id)
);

CREATE TABLE Reservation (
    res_id        INT AUTO_INCREMENT,
    res_date      DATE          NOT NULL,
    PRIMARY KEY (res_id)
);

CREATE TABLE RentalPeriod (
    rent_id       INT AUTO_INCREMENT,
    start_date    DATE,
    end_date      DATE,
    PRIMARY KEY (rent_id)
);

CREATE TABLE Receipt (
    rec_id        INT AUTO_INCREMENT,
    amount        DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','credit','debit','online'),
    PRIMARY KEY (rec_id)
);

CREATE TABLE DamageRecord (
    damage_id     INT AUTO_INCREMENT,
    car_id        INT,
    description   VARCHAR(200),
    repair_cost   DECIMAL(10,2),
    record_date   DATE DEFAULT (CURRENT_DATE),
    PRIMARY KEY (damage_id),
    CONSTRAINT fk_damage_car
        FOREIGN KEY (car_id) REFERENCES Car(car_id)
        ON DELETE CASCADE
);

CREATE TABLE CarInsurance (
    ins_id        INT AUTO_INCREMENT,
    car_id        INT,
    policy_num    VARCHAR(50),
    start_date    DATE,
    end_date      DATE,
    PRIMARY KEY(ins_id),
    CONSTRAINT fk_car_ins_car
        FOREIGN KEY (car_id) REFERENCES Car(car_id)
        ON DELETE CASCADE
);

CREATE TABLE EmployeeInsurance (
    emp_ins_id    INT AUTO_INCREMENT,
    emp_id        INT,
    branch_id     INT,
    policy_num    VARCHAR(50),
    start_date    DATE,
    end_date      DATE,
    PRIMARY KEY(emp_ins_id),
    CONSTRAINT fk_emp_ins_emp
        FOREIGN KEY (emp_id)   REFERENCES Employee(emp_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_emp_ins_branch
        FOREIGN KEY (branch_id)REFERENCES Branch(branch_id)
        ON DELETE CASCADE
);

/*==============================================================*/
/* 2  RELATION / JUNCTION TABLES                                */
/*==============================================================*/

CREATE TABLE EmployeeBranch (              -- employee works in branch
    emp_id     INT,
    branch_id  INT,
    since_date DATE DEFAULT (CURRENT_DATE),
    PRIMARY KEY(emp_id, branch_id),
    FOREIGN KEY (emp_id)    REFERENCES Employee(emp_id)  ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES Branch(branch_id) ON DELETE CASCADE
);

CREATE TABLE BranchCar (                   -- car currently stationed at branch
    car_id     INT,
    branch_id  INT,
    since_date DATE DEFAULT (CURRENT_DATE),
    PRIMARY KEY(car_id, branch_id),
    FOREIGN KEY (car_id)    REFERENCES Car(car_id)       ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES Branch(branch_id) ON DELETE CASCADE
);

CREATE TABLE ReservationCar (              -- reservation includes car(s)
    res_id INT,
    car_id INT,
    PRIMARY KEY (res_id, car_id),
    FOREIGN KEY (res_id) REFERENCES Reservation(res_id)  ON DELETE CASCADE,
    FOREIGN KEY (car_id) REFERENCES Car(car_id)          ON DELETE CASCADE
);

CREATE TABLE CustomerReservation (         -- customer makes reservation
    cus_id INT,
    res_id INT,
    PRIMARY KEY(cus_id, res_id),
    FOREIGN KEY (cus_id) REFERENCES Customer(cus_id)     ON DELETE CASCADE,
    FOREIGN KEY (res_id) REFERENCES Reservation(res_id)  ON DELETE CASCADE
);

CREATE TABLE ReservationReceipt (          -- payment for a reservation
    rec_id INT,
    res_id INT,
    cus_id INT,
    PRIMARY KEY (rec_id),
    FOREIGN KEY (rec_id) REFERENCES Receipt(rec_id)      ON DELETE CASCADE,
    FOREIGN KEY (res_id) REFERENCES Reservation(res_id)  ON DELETE CASCADE,
    FOREIGN KEY (cus_id) REFERENCES Customer(cus_id)     ON DELETE CASCADE
);

CREATE TABLE ReservationRentalPeriod (     -- link reservation to rental period
    rent_id INT,
    res_id  INT,
    PRIMARY KEY (rent_id),
    FOREIGN KEY (rent_id) REFERENCES RentalPeriod(rent_id) ON DELETE CASCADE,
    FOREIGN KEY (res_id)  REFERENCES Reservation(res_id)   ON DELETE CASCADE
);

/*==============================================================*/
/* 3  SYSTEM / LOG TABLES                                       */
/*==============================================================*/

CREATE TABLE Notifications (
    notification_id INT AUTO_INCREMENT,
    car_id          INT,
    message         VARCHAR(255),
    created_at      DATETIME,
    is_read         BOOLEAN DEFAULT FALSE,
    PRIMARY KEY(notification_id)
);

CREATE TABLE SalaryChangeLog (
    log_id       INT AUTO_INCREMENT,
    emp_id       INT,
    old_salary   DECIMAL(10,2),
    new_salary   DECIMAL(10,2),
    changed_at   DATETIME,
    PRIMARY KEY(log_id),
    FOREIGN KEY(emp_id) REFERENCES Employee(emp_id) ON DELETE CASCADE
);

/*==============================================================*/
/* 4  TRIGGERS (4)                                              */
/*==============================================================*/
DELIMITER $$

/* Trigger 1 – rental period sanity (insert & update) */
CREATE TRIGGER trg_check_rental_period
BEFORE INSERT ON RentalPeriod
FOR EACH ROW
BEGIN
    IF NEW.end_date <= NEW.start_date THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT =
            'end_date must be after start_date';
    END IF;
END $$

CREATE TRIGGER trg_check_rental_period_upd
BEFORE UPDATE ON RentalPeriod
FOR EACH ROW
BEGIN
    IF NEW.end_date <= NEW.start_date THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT =
            'end_date must be after start_date';
    END IF;
END $$

/* Trigger 2 – notify on new damage record */
CREATE TRIGGER trg_damage_notification
AFTER INSERT ON DamageRecord
FOR EACH ROW
BEGIN
    INSERT INTO Notifications (car_id, message, created_at)
    VALUES (
        NEW.car_id,
        CONCAT('New damage recorded: ', NEW.description),
        NOW()
    );
END $$

/* Trigger 3 – audit salary changes */
CREATE TRIGGER trg_salary_audit
AFTER UPDATE ON Employee
FOR EACH ROW
BEGIN
    IF OLD.salary <> NEW.salary THEN
        INSERT INTO SalaryChangeLog (emp_id, old_salary, new_salary, changed_at)
        VALUES (OLD.emp_id, OLD.salary, NEW.salary, NOW());
    END IF;
END $$

/* Trigger 4 – insurance date sanity (insert & update) */
CREATE TRIGGER trg_check_insurance
BEFORE INSERT ON CarInsurance
FOR EACH ROW
BEGIN
    IF NEW.end_date <= NEW.start_date THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT =
            'Insurance end_date must be after start_date';
    END IF;
END $$

CREATE TRIGGER trg_check_insurance_upd
BEFORE UPDATE ON CarInsurance
FOR EACH ROW
BEGIN
    IF NEW.end_date <= NEW.start_date THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT =
            'Insurance end_date must be after start_date';
    END IF;
END $$

DELIMITER ;

/*==============================================================*/
/* 5  STORED PROCEDURES (4)                                     */
/*==============================================================*/
DELIMITER $$

/* Proc 1 – Create a full reservation transaction */
CREATE PROCEDURE CreateNewReservation (
    IN p_cus_id     INT,
    IN p_car_id     INT,
    IN p_start_date DATE,
    IN p_end_date   DATE
)
BEGIN
    DECLARE v_res_id  INT;
    DECLARE v_rent_id INT;

    /* core reservation */
    INSERT INTO Reservation (res_date) VALUES (CURDATE());
    SET v_res_id = LAST_INSERT_ID();

    /* rental period */
    INSERT INTO RentalPeriod (start_date, end_date)
    VALUES (p_start_date, p_end_date);
    SET v_rent_id = LAST_INSERT_ID();

    /* links */
    INSERT INTO ReservationRentalPeriod (rent_id, res_id)
    VALUES (v_rent_id, v_res_id);

    INSERT INTO CustomerReservation (cus_id, res_id)
    VALUES (p_cus_id, v_res_id);

    INSERT INTO ReservationCar (res_id, car_id)
    VALUES (v_res_id, p_car_id);
END $$

/* Proc 2 – List cars at a branch */
CREATE PROCEDURE GetBranchCars (IN p_branch_id INT)
BEGIN
    SELECT c.car_id,
           c.plate_number,
           c.brand,
           c.model_year
    FROM   BranchCar bc
    JOIN   Car c ON c.car_id = bc.car_id
    WHERE  bc.branch_id = p_branch_id;
END $$

/* Proc 3 – Car damage history */
CREATE PROCEDURE GetCarDamageHistory (IN p_car_id INT)
BEGIN
    SELECT damage_id,
           description,
           repair_cost,
           record_date
    FROM   DamageRecord
    WHERE  car_id = p_car_id
    ORDER  BY record_date DESC;
END $$

/* Proc 4 – Employee profile with branch list */
CREATE PROCEDURE EmployeePerformanceReport (IN p_emp_id INT)
BEGIN
    SELECT e.full_name,
           e.position,
           e.salary,
           b.location AS branch,
           eb.since_date
    FROM   Employee e
    LEFT JOIN EmployeeBranch eb ON e.emp_id = eb.emp_id
    LEFT JOIN Branch b         ON b.branch_id = eb.branch_id
    WHERE  e.emp_id = p_emp_id;
END $$

DELIMITER ;

/*==============================================================*/
/* 6  SAMPLE DATA – 10 ROWS PER TABLE                           */
/*==============================================================*/

/* ---- Customers (10) ---- */
INSERT INTO Customer (cus_id, full_name, phone, licence_num) VALUES
(1001,'Zeki Yılmaz','554-1111','TR1111'),
(1002,'Aslı Demir' ,'554-2222','TR1112'),
(1003,'Murat Aksoy','554-3333','TR1113'),
(1004,'Gülcan Kaya','554-4444','TR1114'),
(1005,'Tuna Aydın' ,'554-5555','TR1115'),
(1006,'Yeliz Şen'  ,'554-6666','TR1116'),
(1007,'Kaan Polat' ,'554-7777','TR1117'),
(1008,'Buse Çelik' ,'554-8888','TR1118'),
(1009,'Onur Er'    ,'554-9999','TR1119'),
(1010,'Sıla Toprak','554-0000','TR1120');

/* ---- Branches (10) ---- */
INSERT INTO Branch (branch_id, location, phone) VALUES
(200,'Kadıköy' ,'531-5600'),
(201,'Taksim'  ,'555-0102'),
(202,'Beşiktaş','533-2200'),
(203,'Üsküdar' ,'532-9999'),
(204,'Sarıyer' ,'534-1010'),
(205,'Levent'  ,'535-3311'),
(206,'Ataşehir','538-4412'),
(207,'Bakırköy','536-7700'),
(208,'Maltepe' ,'531-5601'),
(209,'Şişli'   ,'535-7788');

/* ---- Employees (10) ---- */
INSERT INTO Employee (emp_id, full_name, phone, position, salary) VALUES
(300,'Efe Öztürk' ,'555-3001','Manager',5000),
(301,'Sevgi Altın','555-3002','Sales'  ,3500),
(302,'Arda Bal'   ,'555-3003','Sales'  ,3400),
(303,'Hülya Aslan','555-3004','Agent'  ,2800),
(304,'Tekin Işık' ,'555-3005','Manager',5200),
(305,'Bilge Sönmez','555-3006','Sales' ,3300),
(306,'Nazan Kurt' ,'555-3007','Agent'  ,2900),
(307,'Tamer Yiğit','555-3008','Sales'  ,3600),
(308,'İpek Gül'   ,'555-3009','Agent'  ,2700),
(309,'Gökhan Ege' ,'555-3010','Director',6000);

/* ---- Cars (10) ---- */
INSERT INTO Car (car_id, plate_number, brand, model_year) VALUES
(50,'34-AAA-50','Toyota' ,2020),
(51,'34-AAA-51','Honda'  ,2021),
(52,'34-AAA-52','Fiat'   ,2022),
(53,'34-AAA-53','Ford'   ,2023),
(54,'34-AAA-54','Opel'   ,2019),
(55,'34-AAA-55','BMW'    ,2018),
(56,'34-AAA-56','VW'     ,2017),
(57,'34-AAA-57','Renault',2016),
(58,'34-AAA-58','Peugeot',2021),
(59,'34-AAA-59','Hyundai',2022);

/* ---- Reservations (10) ---- */
INSERT INTO Reservation (res_id, res_date) VALUES
(400,'2023-06-11'),
(401,'2023-06-12'),
(402,'2023-06-13'),
(403,'2023-06-14'),
(404,'2023-06-15'),
(405,'2023-06-16'),
(406,'2023-06-17'),
(407,'2023-06-18'),
(408,'2023-06-19'),
(409,'2023-06-20');

/* ---- Rental periods (10) ---- */
INSERT INTO RentalPeriod (rent_id, start_date, end_date) VALUES
(500,'2023-07-01','2023-07-02'),
(501,'2023-07-03','2023-07-04'),
(502,'2023-07-05','2023-07-06'),
(503,'2023-07-07','2023-07-08'),
(504,'2023-07-09','2023-07-10'),
(505,'2023-07-11','2023-07-12'),
(506,'2023-07-13','2023-07-14'),
(507,'2023-07-15','2023-07-16'),
(508,'2023-07-17','2023-07-18'),
(509,'2023-07-19','2023-07-20');

/* ---- Receipts (10) ---- */
INSERT INTO Receipt (rec_id, amount, payment_method) VALUES
(600,250,'cash'),
(601,300,'credit'),
(602,180,'cash'),
(603,500,'cash'),
(604,220,'credit'),
(605,280,'credit'),
(606,320,'cash'),
(607,450,'credit'),
(608,200,'cash'),
(609,400,'credit');

/* ---- Damage records (10) ---- */
INSERT INTO DamageRecord (damage_id, car_id, description, repair_cost, record_date) VALUES
(710,50,'Scratch on door',300 ,'2024-01-10'),
(711,51,'Broken mirror'  ,200 ,'2024-01-12'),
(712,52,'Front bumper crack',400,'2024-01-15'),
(713,53,'Flat tyre'      ,150 ,'2024-01-18'),
(714,54,'Windshield chip',350 ,'2024-01-20'),
(715,55,'Headlight fault',250 ,'2024-01-25'),
(716,56,'Engine issue'   ,2000,'2024-02-01'),
(717,57,'Rear bumper scratch',100,'2024-02-05'),
(718,58,'AC failure'     ,500 ,'2024-02-10'),
(719,59,'Gearbox trouble',800 ,'2024-02-14');

/* ---- Car insurance (10) ---- */
INSERT INTO CarInsurance (ins_id, car_id, policy_num, start_date, end_date) VALUES
(730,50,'C-INS-730','2024-01-01','2024-12-31'),
(731,51,'C-INS-731','2024-01-01','2024-12-31'),
(732,52,'C-INS-732','2024-01-01','2024-12-31'),
(733,53,'C-INS-733','2024-01-01','2024-12-31'),
(734,54,'C-INS-734','2024-01-01','2024-12-31'),
(735,55,'C-INS-735','2024-01-01','2024-12-31'),
(736,56,'C-INS-736','2024-01-01','2024-12-31'),
(737,57,'C-INS-737','2024-01-01','2024-12-31'),
(738,58,'C-INS-738','2024-01-01','2024-12-31'),
(739,59,'C-INS-739','2024-01-01','2024-12-31');

/* ---- Employee insurance (10) ---- */
INSERT INTO EmployeeInsurance (emp_ins_id, emp_id, branch_id, policy_num, start_date, end_date) VALUES
(820,300,200,'E-INS-820','2024-01-01','2024-12-31'),
(821,301,201,'E-INS-821','2024-01-01','2024-12-31'),
(822,302,202,'E-INS-822','2024-01-01','2024-12-31'),
(823,303,203,'E-INS-823','2024-01-01','2024-12-31'),
(824,304,204,'E-INS-824','2024-01-01','2024-12-31'),
(825,305,205,'E-INS-825','2024-01-01','2024-12-31'),
(826,306,206,'E-INS-826','2024-01-01','2024-12-31'),
(827,307,207,'E-INS-827','2024-01-01','2024-12-31'),
(828,308,208,'E-INS-828','2024-01-01','2024-12-31'),
(829,309,209,'E-INS-829','2024-01-01','2024-12-31');

/* ---- Employee ↔ Branch (10) ---- */
INSERT INTO EmployeeBranch (emp_id, branch_id, since_date) VALUES
(300,200,'2023-01-01'),
(301,201,'2023-01-01'),
(302,202,'2023-01-01'),
(303,203,'2023-01-01'),
(304,204,'2023-01-01'),
(305,205,'2023-01-01'),
(306,206,'2023-01-01'),
(307,207,'2023-01-01'),
(308,208,'2023-01-01'),
(309,209,'2023-01-01');

/* ---- Branch ↔ Car (10) ---- */
INSERT INTO BranchCar (car_id, branch_id, since_date) VALUES
(50,200,'2023-01-01'),
(51,200,'2023-01-01'),
(52,201,'2023-01-01'),
(53,202,'2023-01-01'),
(54,203,'2023-01-01'),
(55,204,'2023-01-01'),
(56,205,'2023-01-01'),
(57,206,'2023-01-01'),
(58,207,'2023-01-01'),
(59,208,'2023-01-01');

/* ---- Reservation ↔ Car (10) ---- */
INSERT INTO ReservationCar (res_id, car_id) VALUES
(400,50),(401,51),(402,52),(403,53),(404,54),
(405,55),(406,56),(407,57),(408,58),(409,59);

/* ---- Customer ↔ Reservation (10) ---- */
INSERT INTO CustomerReservation (cus_id, res_id) VALUES
(1001,400),(1002,401),(1003,402),(1004,403),(1005,404),
(1006,405),(1007,406),(1008,407),(1009,408),(1010,409);

/* ---- Reservation ↔ RentalPeriod (10) ---- */
INSERT INTO ReservationRentalPeriod (rent_id, res_id) VALUES
(500,400),(501,401),(502,402),(503,403),(504,404),
(505,405),(506,406),(507,407),(508,408),(509,409);

/* ---- Reservation ↔ Receipt (10) ---- */
INSERT INTO ReservationReceipt (rec_id, res_id, cus_id) VALUES
(600,400,1001),(601,401,1002),(602,402,1003),(603,403,1004),(604,404,1005),
(605,405,1006),(606,406,1007),(607,407,1008),(608,408,1009),(609,409,1010);
