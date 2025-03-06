<?php
require '../vendor/autoload.php';

// Clé de l'API (permet d'accéder aux taux de change)
define("API_KEY", "fca_live_ns1BNzLhXFPsG7LPhY1pYVPHKvrnkNo8nO0un0y8"); 

// Dépendances
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

session_start(); // Démarre une session pour stocker des infos temporaires

class CurrencyConverter
{
    private $log;
    private $apiKey;
    private $currencies;
    private $maxRequests;
    private $timeFrame;
    private $ip;

    public function __construct()
    {
        $this->apiKey = API_KEY;
        $this->currencies = ['USD', 'EUR', 'CAD', 'CHF', 'GBP']; // Devises disponibles
        $this->maxRequests = 3; // Nombre max de requêtes autorisées
        $this->timeFrame = 15; // Temps en secondes avant réinitialisation du compteur
        $this->ip = $_SERVER['REMOTE_ADDR']; // Adresse IP du visiteur

        // Création d'un fichier de log pour suivre les conversions et erreurs
        $this->log = new Logger('currency_exchange');
        $this->log->pushHandler(new StreamHandler(__DIR__ . '/logs/exchange.log', Logger::INFO));
    }

    // Vérifie si l'utilisateur a dépassé la limite de requêtes
    public function checkRequestLimit()
    {
        if (!isset($_SESSION['request_log'])) {
            $_SESSION['request_log'] = [];
        }

        // Garde uniquement les requêtes récentes
        $_SESSION['request_log'] = array_filter($_SESSION['request_log'], function ($timestamp) {
            return $timestamp > time() - $this->timeFrame;
        });

        if (count($_SESSION['request_log']) >= $this->maxRequests) {
            $_SESSION['alerts'][] = "Limite de requêtes dépassée par l'IP : $this->ip";
            $this->log->warning("Limite de requêtes dépassée par l'IP : $this->ip");
            return true;
        }
        return false;
    }

    public function getAlerts()
    {
        return $_SESSION['alerts'] ?? [];
    }

    public function clearAlerts()
    {
        $_SESSION['alerts'] = [];
    }

    // Vérifie si les entrées utilisateur sont valides
    public function validateInput($amount, $fromCurrency, $toCurrency)
    {
        $amount = filter_var($amount, FILTER_VALIDATE_FLOAT);
        $fromCurrency = htmlspecialchars(strtoupper(trim($fromCurrency)));
        $toCurrency = htmlspecialchars(strtoupper(trim($toCurrency)));

        // Vérifie si le montant est valide
        if (!$amount || $amount <= 0 || $amount > 9999999) {
            $this->log->warning("Montant invalide: $amount | IP: $this->ip");
            return "<h4>Erreur : Montant invalide.</h4>";
        }

        // Vérifie si les devises sont valides
        if (!in_array($fromCurrency, $this->currencies) || !in_array($toCurrency, $this->currencies)) {
            $this->log->warning("Devise invalide: from=$fromCurrency, to=$toCurrency | IP: $this->ip");
            return "<h4>Erreur : Devise invalide.</h4>";
        }

        return ['amount' => $amount, 'fromCurrency' => $fromCurrency, 'toCurrency' => $toCurrency];
    }

    // Convertit une devise en une autre
    public function convertCurrency($amount, $fromCurrency, $toCurrency)
    {
        $validated = $this->validateInput($amount, $fromCurrency, $toCurrency);
        if (!is_array($validated)) return $validated; // Retourne une erreur si la validation échoue

        $amount = $validated['amount'];
        $fromCurrency = $validated['fromCurrency'];
        $toCurrency = $validated['toCurrency'];

        $url = "https://api.freecurrencyapi.com/v1/latest?apikey=$this->apiKey&currencies=" . implode('%2C', $this->currencies);
        
        $startTime = microtime(true);
        $response = @file_get_contents($url);
        $endTime = microtime(true);
        $apiDuration = ($endTime - $startTime) * 1000;

        if (!$response) {
            $this->log->error("Erreur API : Impossible de récupérer les taux de change.");
            return "<h4>Erreur lors de la récupération des taux de change.</h4>";
        }

        $data = json_decode($response, true);

        if (!isset($data['data'][$fromCurrency]) || !isset($data['data'][$toCurrency])) {
            $this->log->error("Données API invalides ou manquantes.");
            return "<h4>Erreur lors du traitement des taux de change.</h4>";
        }

        $rate = $data['data'][$toCurrency] / $data['data'][$fromCurrency];
        $convertedAmount = number_format($amount * $rate, 2);
        $this->log->info("Conversion : $amount $fromCurrency en $convertedAmount $toCurrency | Taux : $rate | Temps API : $apiDuration ms");

        return "<h4>$amount $fromCurrency = $convertedAmount $toCurrency</h4>";
    }

    public function getSupportedCurrencies()
    {
        return $this->currencies;
    }
}

$converter = new CurrencyConverter();
$result = '';
$alerts = $converter->getAlerts();

// Vérifie si la limite de requêtes est atteinte
if ($converter->checkRequestLimit()) {
    $result = "<h4>Trop de requêtes ! Veuillez patienter avant d'essayer à nouveau.</h4>";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'], $_POST['fromCurrency'], $_POST['toCurrency'])) {
    $_SESSION['request_log'][] = time();
    $amount = $_POST['amount'];
    $fromCurrency = $_POST['fromCurrency'];
    $toCurrency = $_POST['toCurrency'];
    $result = $converter->convertCurrency($amount, $fromCurrency, $toCurrency);
}

// Gestion de l'administration
if (isset($_POST['admin_login'])) {
    if ($_POST['username'] === 'admin' && $_POST['password'] === 'adminpass') {
        $_SESSION['admin'] = true;
    }
}

if (isset($_POST['logout'])) {
    unset($_SESSION['admin']);
}

if (isset($_POST['clear_alerts'])) {
    $converter->clearAlerts();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <title>Convertisseur de devises</title>
</head>

<body>
<div class="container-sm my-5 shadow-lg p-3 mb-5 bg-white rounded">
        <div class="container-fluid">
            <?php if (isset($_SESSION['admin'])): ?>
                <h3>Alertes</h3>
                <?php foreach ($alerts as $alert): ?>
                    <p><?= $alert; ?></p>
                <?php endforeach; ?>
                <form method="POST">
                    <button type="submit" name="clear_alerts" class="btn btn-warning">Effacer les alertes</button>
                    <button type="submit" name="logout" class="btn btn-danger">Déconnexion Admin</button>
                </form>
            <?php else: ?>
                <form method="POST">
                    <input type="text" name="username" placeholder="Nom d'utilisateur" required>
                    <input type="password" name="password" placeholder="Mot de passe" required>
                    <button type="submit" name="admin_login" class="btn btn-primary">Connexion Admin</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <div class="container-sm my-5 shadow-lg p-3 mb-5 bg-white rounded">
        <div class="container-fluid">
            <form method="POST">
                <div class="form-group py-3">
                    <label for="amount">Montant</label>
                    <input type="number" step="0.01" class="form-control" name="amount" placeholder="Entrez un montant"
                        required>
                </div>
                <div class="form-group py-3">
                    <label for="fromCurrency">De</label>
                    <select class="form-control" name="fromCurrency" required>
                        <?php foreach ($converter->getSupportedCurrencies() as $currency)
                            echo "<option value='$currency'>$currency</option>"; ?>
                    </select>
                </div>
                <div class="form-group py-3">
                    <label for="toCurrency">Vers</label>
                    <select class="form-control" name="toCurrency" required>
                        <?php foreach ($converter->getSupportedCurrencies() as $currency)
                            echo "<option value='$currency'>$currency</option>"; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Convertir</button>
            </form>
            <div class="mt-3"> <?= $result; ?> </div>
        </div>
        <div class="container my-5">
            <footer class="text-center text-lg-start">
                <div class="text-center p-3">
                    © 2025 Currency Exchange - Leart Demiri - Louis Robinson-Paris - Timoléon Hede
                </div>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>