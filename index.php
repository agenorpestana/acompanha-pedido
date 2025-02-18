<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

// Obter os dados dos pedidos do dia atual agrupados por status
$query = "SELECT status_pedido, COUNT(*) as count FROM pedidos WHERE DATE(data_pedido) = CURDATE() GROUP BY status_pedido";
$stmt = $conn->prepare($query);
$stmt->execute();
$status_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status_labels = [];
$status_counts = [];

foreach ($status_data as $row) {
    $status_labels[] = $row['status_pedido'];
    $status_counts[] = $row['count'];
}

// Obter os dados dos pedidos dos últimos 6 meses agrupados por mês e status
$query = "
    SELECT DATE_FORMAT(data_pedido, '%Y-%m') as month, status_pedido, COUNT(*) as count 
    FROM pedidos 
    WHERE data_pedido >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month, status_pedido 
    ORDER BY month ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$month_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$months = [];
$status_by_month = [];

foreach ($month_data as $row) {
    $month = $row['month'];
    $status = $row['status_pedido'];
    $count = $row['count'];

    if (!in_array($month, $months)) {
        $months[] = $month;
    }

    if (!isset($status_by_month[$status])) {
        $status_by_month[$status] = [];
    }

    $status_by_month[$status][$month] = $count;
}

foreach ($status_by_month as $status => $data) {
    foreach ($months as $month) {
        if (!isset($status_by_month[$status][$month])) {
            $status_by_month[$status][$month] = 0;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Adicionado para responsividade -->
    <title>Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #007bff;
            color: #fff;
            padding: 20px;
            text-align: center;
        }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 80vh;
            flex-direction: column;
        }
        .menu {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-bottom: 20px;
        }
        .menu a {
            display: block;
            margin: 10px 0;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .menu a:hover {
            background-color: #0056b3;
        }
        .logout-button {
            background-color: #dc3545; /* cor vermelha */
        }
        .logout-button:hover {
            background-color: #c82333; /* cor vermelha mais escura ao passar o mouse */
        }
        .charts {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            padding: 20px;
            gap:15px;
        }
        .chart-container {
            width: 300px;
            height: 300px;
            margin: 10px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="header">
        <h1>Bem-vindo, <?php echo $_SESSION['usuario']['nome']; ?></h1>
    </div>
    <div class="container">
        <div class="menu">
            <a href="listar_funcionarios.php">Cadastrar Funcionário</a>
            <a href="pedido.php">Acessar Pedidos</a>
            <a href="logout.php" class="logout-button">Sair</a>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="charts">
        <div class="chart-container">
            <h2>Pedidos por Status (Hoje)</h2>
            <canvas id="statusChart"></canvas>
        </div>
        
        <div class="chart-container">
            <h2>Pedidos por Status e Período (Últimos 6 meses)</h2>
            <canvas id="periodChart"></canvas>
        </div>
    </div>

    <script>
        // Gráfico de Pizza: Pedidos por Status (Hoje)
        const statusLabels = <?php echo json_encode($status_labels); ?>;
        const statusCounts = <?php echo json_encode($status_counts); ?>;
        
        const ctxStatus = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(ctxStatus, {
            type: 'pie',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusCounts,
                    backgroundColor: ['#ffcccb', '#99ccff', '#ccffcc'],
                    hoverBackgroundColor: ['#FF6384', '#36A2EB', '#6ef26e']
                }]
            }
        });
        
        // Gráfico de Barras: Pedidos por Status e Período (Últimos 6 meses)
        const months = <?php echo json_encode($months); ?>;
        const statusByMonth = <?php echo json_encode($status_by_month); ?>;

        const colors = {
            'pedido feito': '#6ef26e',
            'em producao': '#6ef26e',
            'concluido': '#6ef26e'
        };
        
        const datasets = Object.keys(statusByMonth).map(status => {
            return {
                label: status,
                data: months.map(month => statusByMonth[status][month]),
                backgroundColor: colors[status] 
            };
        });
        
        const ctxPeriod = document.getElementById('periodChart').getContext('2d');
        const periodChart = new Chart(ctxPeriod, {
            type: 'bar',
            data: {
                labels: months,
                datasets: datasets
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true
                    }
                }
            }
        });
        
        // Função para gerar cores aleatórias
        /* function getRandomColor() {
            const letters = '0123456789ABCDEF';
            let color = '#';
            for (let i = 0; i < 6; i++) {
                color += letters[Math.floor(Math.random() * 16)];
            }
            return color;
        } */
    </script>
</body>
</html>
