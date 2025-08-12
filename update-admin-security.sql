-- UPDATE ADMIN SECURITY SYSTEM
-- =============================

-- Tạo bảng admin_access_log (cho admin security)
CREATE TABLE IF NOT EXISTS admin_access_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_ip (ip_address),
    INDEX idx_created (created_at)
);

-- Tạo bảng blocked_ips (cho IP blocking)
CREATE TABLE IF NOT EXISTS blocked_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) UNIQUE,
    reason TEXT,
    blocked_by INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_ip (ip_address),
    INDEX idx_active (is_active),
    INDEX idx_expires (expires_at)
);

-- Thêm cột admin_key_verified vào bảng users (nếu cần)
ALTER TABLE users ADD COLUMN IF NOT EXISTS admin_key_verified BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN IF NOT EXISTS admin_key_expires_at TIMESTAMP NULL;

-- Tạo index cho performance
CREATE INDEX IF NOT EXISTS idx_admin_access_ip_time ON admin_access_log (ip_address, created_at);
CREATE INDEX IF NOT EXISTS idx_admin_access_action_time ON admin_access_log (action, created_at);

-- Thêm comment cho bảng
ALTER TABLE admin_access_log COMMENT = 'Admin access log for security monitoring';
ALTER TABLE blocked_ips COMMENT = 'Blocked IP addresses for security';

-- Removed sample data insertion for production readiness

-- Update existing admin user to have admin key verification
-- Keep existing admin accounts unchanged; admins can be managed via panel
