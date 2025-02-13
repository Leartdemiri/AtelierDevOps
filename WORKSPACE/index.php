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
            <ul class="nav col-12 col-md-auto mb-2 mb-md-0">
                <a class="btn bg-light" href="index.html">Home</a>
            </ul>

            <form id="exchangeForm">
                <div class="form-group py-3">
                    <label for="amount">Amount</label>
                    <input type="number" class="form-control" id="amount" placeholder="Enter amount" required>
                </div>

                <div class="form-group py-3">
                    <label for="fromCurrency">From</label>
                    <select class="form-control" id="fromCurrency" required>
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                        <option value="CAD">CAD</option>
                    </select>
                </div>

                <div class="form-group py-3">
                    <label for="toCurrency">To</label>
                    <select class="form-control" id="toCurrency" required>
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                        <option value="CAD">CAD</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Convert</button>
            </form>

            <div class="mt-3" id="result"></div>
        </div>

        <div class="container my-5">
            <footer class="text-center text-lg-start">  
                <div class="text-center p-3">
                    © 2025 Currency Exchange - Leart Demiri 
                </div>
            </footer>
        </div>
    </div>

    <script>
        const apiKey = 'fca_live_5xzXI1hluDMK6cpnGS9iy5yWsoKgYxV4lpyAbbMk'; // Replace with your FreeCurrencyAPI key
        document.getElementById("exchangeForm").addEventListener("submit", async function(event) {
            event.preventDefault();
            let amount = document.getElementById("amount").value;
            let fromCurrency = document.getElementById("fromCurrency").value;
            let toCurrency = document.getElementById("toCurrency").value;
            
            let response = await fetch(`https://api.currencyapi.com/v3/latest?apikey=${apiKey}&currencies=USD,EUR,CAD`);
            let data = await response.json();
            let rate = data.data[toCurrency].value / data.data[fromCurrency].value;
            let convertedAmount = (amount * rate).toFixed(2);
            
            document.getElementById("result").innerHTML = `<h4>${amount} ${fromCurrency} = ${convertedAmount} ${toCurrency}</h4>`;
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
