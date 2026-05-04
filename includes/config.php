<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'must_hostel');

define('SITE_NAME', 'MUST Hostel Allocation System');
define('SITE_URL', 'http://localhost/must_hostel');
define('MAX_CAPACITY', 500);

// Email settings (use PHPMailer or built-in mail())
define('MAIL_FROM', 'noreply@must.ac.mw');
define('MAIL_FROM_NAME', 'MUST Hostel Office');
define('ACCOMMODATION_FEE', 80000); // MWK

// mysqli Connection wrapper
class SimpleDB {
    private $mysqli;

    public function __construct($host, $user, $pass, $name) {
        $this->mysqli = new mysqli($host, $user, $pass, $name);
        if ($this->mysqli->connect_error) {
            die("<div style='font-family:monospace;color:red;padding:20px'>
                 <b>Database Connection Error:</b> " . $this->mysqli->connect_error . "<br>
                 Please check your database configuration in includes/config.php
                 </div>");
        }
        $this->mysqli->set_charset('utf8');
    }

    public function prepare($sql) {
        return new SimpleStatement($this->mysqli, $sql);
    }

    public function query($sql) {
        $result = $this->mysqli->query($sql);
        if ($result === false) {
            die("<div style='font-family:monospace;color:red;padding:20px'>
                 <b>Database Query Error:</b> " . $this->mysqli->error . "<br>
                 SQL: " . htmlspecialchars($sql) . "
                 </div>");
        }
        return new SimpleResult($result);
    }

    public function error() {
        return $this->mysqli->error;
    }
}

class SimpleStatement {
    private $stmt;
    private $result;

    public function __construct($mysqli, $sql) {
        $this->stmt = $mysqli->prepare($sql);
        if ($this->stmt === false) {
            die("<div style='font-family:monospace;color:red;padding:20px'>
                 <b>Statement Prepare Error:</b> " . $mysqli->error . "<br>
                 SQL: " . htmlspecialchars($sql) . "
                 </div>");
        }
    }

    public function execute($params = []) {
        if (!empty($params)) {
            $types = '';
            $refs = [];
            foreach ($params as $key => $value) {
                if (is_int($value)) {
                    $types .= 'i';
                } elseif (is_float($value)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $refs[$key] = &$params[$key];
            }
            array_unshift($refs, $types);
            call_user_func_array([$this->stmt, 'bind_param'], $refs);
        }

        if (!$this->stmt->execute()) {
            die("<div style='font-family:monospace;color:red;padding:20px'>
                 <b>Statement Execute Error:</b> " . $this->stmt->error . "
                 </div>");
        }

        $this->result = $this->stmt->get_result();
        return $this;
    }

    public function fetch() {
        if ($this->result === null) {
            return null;
        }
        return $this->result->fetch_assoc();
    }

    public function fetchAll() {
        if ($this->result === null) {
            return [];
        }
        return $this->result->fetch_all(MYSQLI_ASSOC);
    }

    public function fetchColumn() {
        if ($this->result === null) {
            return false;
        }
        $row = $this->result->fetch_row();
        return $row ? $row[0] : false;
    }

    public function get_result() {
        return $this->result;
    }

    public function close() {
        if ($this->stmt) {
            $this->stmt->close();
        }
    }
}

class SimpleResult {
    private $result;

    public function __construct($result) {
        $this->result = $result;
    }

    public function fetch() {
        return $this->result->fetch_assoc();
    }

    public function fetchAll() {
        return $this->result->fetch_all(MYSQLI_ASSOC);
    }

    public function fetchColumn() {
        $row = $this->result->fetch_row();
        return $row ? $row[0] : false;
    }

    public function num_rows() {
        return $this->result->num_rows;
    }
}

function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new SimpleDB(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    }
    return $db;
}

// Generate email from reg number
function genEmail($regNumber) {
    return strtolower(trim($regNumber)) . '@must.ac.mw';
}

// Generate invoice number
function genInvoiceNumber() {
    return 'INV-MUST-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
}

// Generate receipt number
function genReceiptNumber() {
    return 'RCP-MUST-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
}

// Send email (basic PHP mail wrapper)
function sendEmail($to, $subject, $htmlBody, $type = 'general') {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
    
    $result = @mail($to, $subject, $htmlBody, $headers);
    
    // Log the email
    try {
        $db = getDB();
        $db->prepare("INSERT INTO email_logs (recipient_email, subject, type, status) VALUES (?,?,?,?)")
           ->execute([$to, $subject, $type, $result ? 'sent' : 'failed']);
    } catch(Exception $e) {}
    
    return $result;
}

// Session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth helpers
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin($role = null) {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
    if ($role && $_SESSION['role'] !== $role) {
        // Check array of roles
        if (is_array($role) && !in_array($_SESSION['role'], $role)) {
            header('Location: ' . SITE_URL . '/index.php?error=unauthorized');
            exit;
        } elseif (!is_array($role)) {
            header('Location: ' . SITE_URL . '/index.php?error=unauthorized');
            exit;
        }
    }
}

function currentUser() {
    return $_SESSION ?? [];
}

// Flash messages
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Sanitize input
function clean($str) {
    return htmlspecialchars(strip_tags(trim($str)));
}
?>
