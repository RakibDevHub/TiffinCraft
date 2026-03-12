-- =============================================
-- TIFFINCRAFT DATABASE SCHEMA
-- Complete SQL file with all sequences, tables, and triggers
-- =============================================

-- =============================================
-- 1. SEQUENCES
-- =============================================
CREATE SEQUENCE user_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE kitchen_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE category_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE menu_item_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE area_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE order_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE plan_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE subscription_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE payment_transactions_seq START WITH 1 INCREMENT BY 1;

-- =============================================
-- 2. CORE TABLES
-- =============================================

-- 2.1 USERS TABLE
CREATE TABLE users (
    user_id           NUMBER DEFAULT user_seq.NEXTVAL PRIMARY KEY,
    name              VARCHAR2(100) NOT NULL,
    email             VARCHAR2(100) UNIQUE NOT NULL,
    phone             VARCHAR2(20) NOT NULL,
    password_hash     VARCHAR2(255) NOT NULL,
    profile_image     VARCHAR2(255),
    gender            VARCHAR2(20),
    role              VARCHAR2(10) DEFAULT 'buyer' NOT NULL
        CONSTRAINT chk_user_role CHECK (role IN ('buyer','seller','admin')),
    status            VARCHAR2(20) DEFAULT 'pending' NOT NULL,
    verification_token VARCHAR2(64),
    token_expires_at  TIMESTAMP,
    created_at        TIMESTAMP DEFAULT SYSTIMESTAMP,
    updated_at        TIMESTAMP DEFAULT SYSTIMESTAMP
);

-- 2.2 KITCHENS TABLE
CREATE TABLE kitchens (
    kitchen_id        NUMBER DEFAULT kitchen_seq.NEXTVAL PRIMARY KEY,
    owner_id          NUMBER NOT NULL
        CONSTRAINT fk_kitchen_owner REFERENCES users(user_id),
    name              VARCHAR2(100) NOT NULL,
    description       CLOB,
    cover_image       VARCHAR2(255),
    address           VARCHAR(255) NOT NULL,
    google_maps_url   VARCHAR2(255),
    years_experience  NUMBER(2),
    signature_dish    VARCHAR2(100),
    avg_prep_time     NUMBER(3) DEFAULT 30,
    is_halal          NUMBER(1) DEFAULT 1,
    cleanliness_pledge NUMBER(1) DEFAULT 0,
    approval_status   VARCHAR2(20) DEFAULT 'pending'
        CONSTRAINT chk_approval_status CHECK (LOWER(approval_status) IN ('pending','approved','rejected')),
    created_at        TIMESTAMP DEFAULT SYSTIMESTAMP,
    updated_at        TIMESTAMP DEFAULT SYSTIMESTAMP
);

-- 2.3 SERVICE_AREAS TABLE
CREATE TABLE service_areas (
    area_id           NUMBER DEFAULT area_seq.NEXTVAL PRIMARY KEY,
    name              VARCHAR2(100) NOT NULL,
    city              VARCHAR2(50) DEFAULT 'Dhaka' NOT NULL,
    status            VARCHAR2(10) DEFAULT 'active' CHECK (status IN ('active','inactive')),
    CONSTRAINT uq_service_area UNIQUE (name, city)
);

-- 2.4 KITCHEN_SERVICE_ZONES TABLE
CREATE TABLE kitchen_service_zones (
    kitchen_id        NUMBER NOT NULL
        CONSTRAINT fk_ksz_kitchen REFERENCES kitchens(kitchen_id) ON DELETE CASCADE,
    area_id           NUMBER NOT NULL
        CONSTRAINT fk_ksz_area REFERENCES service_areas(area_id),
    delivery_fee      NUMBER(10,2) DEFAULT 30,
    min_order         NUMBER(10,2) DEFAULT 150,
    PRIMARY KEY (kitchen_id, area_id)
);

-- =============================================
-- 3. CATEGORIES & MENU TABLES
-- =============================================

-- 3.1 CATEGORIES TABLE
CREATE TABLE categories (
    category_id       NUMBER DEFAULT category_seq.NEXTVAL PRIMARY KEY,
    name              VARCHAR2(50) NOT NULL,
    description       VARCHAR2(200),
    image             VARCHAR(255)
);

-- 3.2 MENU_ITEMS TABLE
CREATE TABLE menu_items (
    item_id           NUMBER DEFAULT menu_item_seq.NEXTVAL PRIMARY KEY,
    kitchen_id        NUMBER NOT NULL
        CONSTRAINT fk_menu_kitchen REFERENCES kitchens(kitchen_id) ON DELETE CASCADE,
    name              VARCHAR2(100) NOT NULL,
    description       CLOB,
    portion_size      VARCHAR2(20),
    price             NUMBER(10,2) NOT NULL,
    spice_level       NUMBER(1) DEFAULT 1
        CONSTRAINT chk_spice_level CHECK (spice_level BETWEEN 1 AND 3),
    daily_stock       NUMBER DEFAULT 10,
    is_available      NUMBER(1) DEFAULT 1,
    item_image        VARCHAR2(255),
    created_at        TIMESTAMP DEFAULT SYSTIMESTAMP,
    updated_at        TIMESTAMP DEFAULT SYSTIMESTAMP
);

-- 3.3 MENU_ITEM_CATEGORIES TABLE (Junction table)
CREATE TABLE menu_item_categories (
    item_id           NUMBER NOT NULL
        CONSTRAINT fk_mic_item REFERENCES menu_items(item_id) ON DELETE CASCADE,
    category_id       NUMBER NOT NULL
        CONSTRAINT fk_mic_category REFERENCES categories(category_id),
    PRIMARY KEY (item_id, category_id)
);

-- =============================================
-- 4. ORDERS TABLES
-- =============================================

-- 4.1 ORDERS TABLE
CREATE TABLE orders (
    order_id          NUMBER DEFAULT order_seq.NEXTVAL PRIMARY KEY,
    buyer_id          NUMBER NOT NULL
        CONSTRAINT fk_order_buyer REFERENCES users(user_id),
    kitchen_id        NUMBER NOT NULL
        CONSTRAINT fk_order_kitchen REFERENCES kitchens(kitchen_id),
    delivery_area_id  NUMBER
        CONSTRAINT fk_order_area REFERENCES service_areas(area_id),
    delivery_address  CLOB NOT NULL,
    contact_phone     VARCHAR2(20) NOT NULL,
    total_amount      NUMBER(10,2) NOT NULL,
    delivery_fee      NUMBER(10,2) DEFAULT 0,
    status            VARCHAR2(20) DEFAULT 'PENDING'
        CONSTRAINT chk_order_status CHECK (status IN ('PENDING', 'ACCEPTED', 'READY', 'DELIVERED', 'CANCELLED')),
    estimated_delivery_time NUMBER(3),
    actual_delivery_time TIMESTAMP,
    created_at        TIMESTAMP DEFAULT SYSTIMESTAMP,
    updated_at        TIMESTAMP DEFAULT SYSTIMESTAMP,
    buyer_delete      NUMBER(1) DEFAULT 0,
    cancellation_reason VARCHAR2(255),
    cancel_by VARCHAR2(10)
);

-- 4.2 ORDER_ITEMS TABLE
CREATE TABLE order_items (
    order_item_id     NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id          NUMBER NOT NULL
        CONSTRAINT fk_oi_order REFERENCES orders(order_id) ON DELETE CASCADE,
    item_id           NUMBER
        CONSTRAINT fk_oi_item REFERENCES menu_items(item_id),
    quantity          NUMBER NOT NULL,
    price_at_order    NUMBER(10,2) NOT NULL,
    special_request   VARCHAR2(100)
);

-- =============================================
-- 5. SUBSCRIPTIONS TABLES
-- =============================================

-- 5.1 SUBSCRIPTION_PLANS TABLE
CREATE TABLE subscription_plans (
    plan_id           NUMBER DEFAULT plan_seq.NEXTVAL PRIMARY KEY,
    plan_name         VARCHAR2(50) NOT NULL,
    description       CLOB,
    monthly_fee       NUMBER(6) DEFAULT 0 NOT NULL,
    commission_rate   NUMBER(5,2) NOT NULL,
    max_items         NUMBER(2) DEFAULT 3,
    is_active         NUMBER(1) DEFAULT 0,
    is_highlight      NUMBER(1) DEFAULT 0,
    created_at        TIMESTAMP DEFAULT SYSTIMESTAMP,
    updated_at        TIMESTAMP DEFAULT SYSTIMESTAMP
);

-- 5.2 SELLER_SUBSCRIPTIONS TABLE
CREATE TABLE seller_subscriptions (
    subscription_id   NUMBER DEFAULT subscription_seq.NEXTVAL PRIMARY KEY,
    seller_id         NUMBER NOT NULL
        CONSTRAINT fk_subscription_seller REFERENCES users(user_id),
    plan_id           NUMBER NOT NULL
        CONSTRAINT fk_subscription_plan REFERENCES subscription_plans(plan_id),
    start_date        DATE NOT NULL,
    end_date          DATE NOT NULL,
    status            VARCHAR2(20) DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','PENDING','EXPIRED','CANCELLED')),
    change_type       VARCHAR2(20) DEFAULT 'NEW' CHECK (change_type IN ('NEW','UPGRADE','DOWNGRADE','RENEWAL')),
    created_at        TIMESTAMP DEFAULT SYSTIMESTAMP NOT NULL,
    updated_at        TIMESTAMP
);

-- =============================================
-- 6. PAYMENTS & PAYOUTS TABLES
-- =============================================

-- 6.1 PAYMENT_TRANSACTIONS TABLE
CREATE TABLE payment_transactions ( 
    id                NUMBER DEFAULT payment_transactions_seq.NEXTVAL PRIMARY KEY,
    transaction_id    VARCHAR2(50) UNIQUE NOT NULL,
    user_id           NUMBER NOT NULL
        CONSTRAINT pt_fk_user REFERENCES users(user_id),
    amount            NUMBER(10,2) NOT NULL,
    currency          VARCHAR2(3) DEFAULT 'BDT',
    transaction_type  VARCHAR2(20) NOT NULL
        CONSTRAINT pt_chk_type CHECK (transaction_type IN ('PAYMENT','PAYOUT')),
    reference_type    VARCHAR2(20) NOT NULL
        CONSTRAINT pt_chk_ref_type CHECK (reference_type IN ('ORDER','SUBSCRIPTION','WITHDRAWAL','REFUND')),
    reference_id      NUMBER,
    payment_method    VARCHAR2(50), 
    status            VARCHAR2(20) NOT NULL
        CONSTRAINT pt_chk_status CHECK (status IN ('PENDING','SUCCESS','FAILED','CANCELLED')),
    description       VARCHAR2(500),
    gateway_response  CLOB,
    message           VARCHAR2(500),
    metadata          CLOB CONSTRAINT check_metadata_json CHECK (metadata IS NULL OR metadata IS JSON),
    created_at        TIMESTAMP DEFAULT SYSTIMESTAMP NOT NULL,
    updated_at        TIMESTAMP DEFAULT SYSTIMESTAMP NOT NULL
);

-- 6.2 REFUND_REQUESTS TABLE
CREATE TABLE refund_requests (
    refund_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id NUMBER NOT NULL,
    buyer_id NUMBER NOT NULL,
    amount NUMBER(10,2) NOT NULL,
    method VARCHAR2(50) NOT NULL,
    account_details VARCHAR2(255) NOT NULL,
    reason VARCHAR2(500),
    status VARCHAR2(20) DEFAULT 'PENDING' CHECK (status IN ('PENDING', 'APPROVED', 'REJECTED', 'PROCESSED')),
    created_at TIMESTAMP DEFAULT SYSTIMESTAMP,
    updated_at TIMESTAMP DEFAULT SYSTIMESTAMP,
    admin_notes VARCHAR2(500),
    buyer_delete NUMBER(1) DEFAULT 0,
    CONSTRAINT fk_refund_order FOREIGN KEY (order_id) REFERENCES orders(order_id),
    CONSTRAINT fk_refund_buyer FOREIGN KEY (buyer_id) REFERENCES users(user_id)
);

-- 6.3 WITHDRAW_REQUESTS TABLE
CREATE TABLE withdraw_requests (
    withdraw_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    seller_id NUMBER NOT NULL,
    amount NUMBER(10,2) NOT NULL,
    method VARCHAR2(50) NOT NULL,
    account_details VARCHAR2(255) NOT NULL,
    status VARCHAR2(20) DEFAULT 'PENDING' 
        CHECK (status IN ('PENDING', 'APPROVED', 'REJECTED', 'PROCESSED')),
    created_at TIMESTAMP DEFAULT SYSTIMESTAMP,
    updated_at TIMESTAMP DEFAULT SYSTIMESTAMP,
    admin_notes VARCHAR2(500),
    CONSTRAINT fk_withdraw_seller FOREIGN KEY (seller_id) REFERENCES users(user_id)
);

-- =============================================
-- 7. BUYER ENGAGEMENT TABLES
-- =============================================

-- 7.1 CART TABLE
CREATE TABLE cart (
    cart_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id NUMBER NOT NULL
        CONSTRAINT fk_cart_user REFERENCES users(user_id) ON DELETE CASCADE,
    item_id NUMBER NOT NULL
        CONSTRAINT fk_cart_item REFERENCES menu_items(item_id) ON DELETE CASCADE,
    quantity NUMBER DEFAULT 1 NOT NULL CHECK (quantity > 0),
    added_at TIMESTAMP DEFAULT SYSTIMESTAMP,
    CONSTRAINT uk_cart_user_item UNIQUE (user_id, item_id)
);

-- 7.2 FAVORITES TABLE
CREATE TABLE favorites (
    favorite_id   NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id       NUMBER NOT NULL
        CONSTRAINT fk_fav_user REFERENCES users(user_id) ON DELETE CASCADE,
    reference_type VARCHAR2(20) NOT NULL
        CONSTRAINT chk_fav_type CHECK (reference_type IN ('KITCHEN','ITEM')),
    reference_id   NUMBER NOT NULL,
    added_at       TIMESTAMP DEFAULT SYSTIMESTAMP,
    CONSTRAINT uq_fav UNIQUE (user_id, reference_type, reference_id)
);

-- 7.3 REVIEWS TABLE
CREATE TABLE reviews (
    review_id           NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    reviewer_id         NUMBER, 
    reference_id        NUMBER,
    reference_type      VARCHAR2(20) NOT NULL
        CONSTRAINT chk_rev_type CHECK (reference_type IN ('KITCHEN','ITEM','TIFFINCRAFT')),
    rating              NUMBER(1) CHECK (rating BETWEEN 1 AND 5),
    comments            CLOB,
    review_date         TIMESTAMP DEFAULT SYSTIMESTAMP,
    status              VARCHAR2(20) DEFAULT 'PUBLIC' 
        CHECK (status IN ('PUBLIC', 'HIDDEN', 'REPORTED')),
    hidden_by           NUMBER REFERENCES users(user_id),
    hidden_at           TIMESTAMP,
    hidden_reason       VARCHAR2(500),
    created_at          TIMESTAMP DEFAULT SYSTIMESTAMP,
    updated_at          TIMESTAMP DEFAULT SYSTIMESTAMP
);

-- =============================================
-- 8. ADMIN TABLES
-- =============================================

-- 8.1 SUSPENSIONS TABLE
CREATE TABLE suspensions (
    suspension_id       NUMBER GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    reference_id        NUMBER NOT NULL,
    reference_type      VARCHAR2(20) NOT NULL
        CONSTRAINT chk_sus_type CHECK (reference_type IN ('KITCHEN','USER')),
    reason              VARCHAR2(255) NOT NULL,
    suspended_at        TIMESTAMP DEFAULT SYSTIMESTAMP,
    suspended_until     TIMESTAMP NULL,
    created_by          NUMBER,
    status              VARCHAR2(20) DEFAULT 'active'
);

-- =============================================
-- 9. INDEXES
-- =============================================
CREATE INDEX idx_kitchen_owner ON kitchens(owner_id);
CREATE INDEX idx_orders_buyer ON orders(buyer_id);
CREATE INDEX idx_reviews_kitchen ON reviews(reference_id, reference_type);
CREATE INDEX idx_favorites_user ON favorites(user_id);
CREATE INDEX idx_payment_reference ON payment_transactions(reference_type, reference_id);
CREATE INDEX idx_menu_kitchen ON menu_items(kitchen_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_kitchen ON orders(kitchen_id);
CREATE INDEX idx_subscription_seller ON seller_subscriptions(seller_id);
CREATE INDEX idx_subscription_dates ON seller_subscriptions(start_date, end_date);

-- =============================================
-- 10. TRIGGERS
-- =============================================

-- 10.1 UPDATE TIMESTAMP TRIGGER FOR PAYMENT_TRANSACTIONS
CREATE OR REPLACE TRIGGER pt_update_trigger
BEFORE UPDATE ON payment_transactions
FOR EACH ROW
BEGIN
    :NEW.updated_at := SYSTIMESTAMP;
END;
/

-- 10.2 UPDATE TIMESTAMP TRIGGER FOR USERS
CREATE OR REPLACE TRIGGER users_update_trigger
BEFORE UPDATE ON users
FOR EACH ROW
BEGIN
    :NEW.updated_at := SYSTIMESTAMP;
END;
/

-- 10.3 UPDATE TIMESTAMP TRIGGER FOR KITCHENS
CREATE OR REPLACE TRIGGER kitchens_update_trigger
BEFORE UPDATE ON kitchens
FOR EACH ROW
BEGIN
    :NEW.updated_at := SYSTIMESTAMP;
END;
/

-- 10.4 UPDATE TIMESTAMP TRIGGER FOR MENU_ITEMS
CREATE OR REPLACE TRIGGER menu_items_update_trigger
BEFORE UPDATE ON menu_items
FOR EACH ROW
BEGIN
    :NEW.updated_at := SYSTIMESTAMP;
END;
/

-- 10.5 UPDATE TIMESTAMP TRIGGER FOR ORDERS
CREATE OR REPLACE TRIGGER orders_update_trigger
BEFORE UPDATE ON orders
FOR EACH ROW
BEGIN
    :NEW.updated_at := SYSTIMESTAMP;
END;
/

-- 10.6 UPDATE TIMESTAMP TRIGGER FOR REVIEWS
CREATE OR REPLACE TRIGGER reviews_update_trigger
BEFORE UPDATE ON reviews
FOR EACH ROW
BEGIN
    :NEW.updated_at := SYSTIMESTAMP;
END;
/

-- 10.7 UPDATE TIMESTAMP TRIGGER FOR SUBSCRIPTION_PLANS
CREATE OR REPLACE TRIGGER subscription_plans_update_trigger
BEFORE UPDATE ON subscription_plans
FOR EACH ROW
BEGIN
    :NEW.updated_at := SYSTIMESTAMP;
END;
/

-- 10.8 UPDATE TIMESTAMP TRIGGER FOR SELLER_SUBSCRIPTIONS
CREATE OR REPLACE TRIGGER seller_subscriptions_update_trigger
BEFORE UPDATE ON seller_subscriptions
FOR EACH ROW
BEGIN
    :NEW.updated_at := SYSTIMESTAMP;
END;
/

-- 10.9 UPDATE TIMESTAMP TRIGGER FOR REFUND_REQUESTS
CREATE OR REPLACE TRIGGER refund_requests_update_trigger
BEFORE UPDATE ON refund_requests
FOR EACH ROW
BEGIN
    :NEW.updated_at := SYSTIMESTAMP;
END;
/

-- 10.10 UPDATE TIMESTAMP TRIGGER FOR WITHDRAW_REQUESTS
CREATE OR REPLACE TRIGGER withdraw_requests_update_trigger
BEFORE UPDATE ON withdraw_requests
FOR EACH ROW
BEGIN
    :NEW.updated_at := SYSTIMESTAMP;
END;
/

-- =============================================
-- END OF SCHEMA
-- =============================================