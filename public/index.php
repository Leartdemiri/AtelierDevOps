<?php
require '../vendor/autoload.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

session_start();
$log = new Logger('currency_exchange');
$log->pushHandler(new StreamHandler(__DIR__.'/logs/exchange.log', Logger::INFO));

$apiKey = 'fca_live_ns1BNzLhXFPsG7LPhY1pYVPHKvrnkNo8nO0un0y8';
$currencies = ['USD', 'EUR', 'CAD', 'CHF', 'GBP'];
$result = '';

// Rate limiting settings
$maxRequests = 5;
$timeFrame = 60; // 60 seconds
$ip = $_SERVER['REMOTE_ADDR'];

if (!isset($_SESSION['request_log'])) {
    $_SESSION['request_log'] = [];
}

// Filter old requests
$_SESSION['request_log'] = array_filter($_SESSION['request_log'], function($timestamp) use ($timeFrame) {
    return $timestamp > time() - $timeFrame;
});

if (count($_SESSION['request_log']) >= $maxRequests) {
    $result = "<h4>Too many requests! Please wait before trying again.</h4>";
    $log->warning("Rate limit exceeded by IP: $ip");
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['request_log'][] = time();
        
        $amount = $_POST['amount'];
        $fromCurrency = $_POST['fromCurrency'];
        $toCurrency = $_POST['toCurrency'];

        if (in_array($fromCurrency, $currencies) && in_array($toCurrency, $currencies) && is_numeric($amount) && $amount > 0) {
            $url = "https://api.freecurrencyapi.com/v1/latest?apikey=$apiKey&currencies=" . implode('%2C', $currencies);
            $response = file_get_contents($url);
            $data = json_decode($response, true);
            
            if ($data && isset($data['data'][$fromCurrency]) && isset($data['data'][$toCurrency])) {
                $rate = $data['data'][$toCurrency] / $data['data'][$fromCurrency];
                $convertedAmount = number_format($amount * $rate, 2);
                $result = "<h4>$amount $fromCurrency = $convertedAmount $toCurrency</h4>";
                $log->info("Conversion: $amount $fromCurrency to $convertedAmount $toCurrency at rate $rate");
            } else {
                $result = "<h4>Error fetching exchange rates.</h4>";
                $log->error("Failed to retrieve exchange rates.");
            }
        } else {
            $result = "<h4>Invalid input.</h4>";
            $log->warning("Invalid input: amount=$amount, from=$fromCurrency, to=$toCurrency");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <title>Currency Exchange</title>
</head>
<body>
    <div class="container-sm my-5 shadow-lg p-3 mb-5 bg-white rounded">
        <div class="container-fluid">
            <form method="POST">
                <div class="form-group py-3">
                    <label for="amount">Amount</label>
                    <input type="number" class="form-control" name="amount" placeholder="Enter amount" required>
                </div>
                <div class="form-group py-3">
                    <label for="fromCurrency">From</label>
                    <select class="form-control" name="fromCurrency" required>
                        <?php foreach ($currencies as $currency) echo "<option value='$currency'>$currency</option>"; ?>
                    </select>
                </div>
                <div class="form-group py-3">
                    <label for="toCurrency">To</label>
                    <select class="form-control" name="toCurrency" required>
                        <?php foreach ($currencies as $currency) echo "<option value='$currency'>$currency</option>"; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Convert</button>
            </form>
            <div class="mt-3"> <?= $result; ?> </div>
        </div>
        <div class="container my-5">
            <footer class="text-center text-lg-start">  
                <div class="text-center p-3">
                    © 2025 Currency Exchange - Leart Demiri 
                </div>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
