<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get election ID from URL or use the most recent election
$election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;

if ($election_id === 0) {
    $stmt = $conn->query("SELECT id FROM elections ORDER BY created_at DESC LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $election_id = $result ? $result['id'] : 0;
}

// Get election details
$stmt = $conn->prepare("SELECT * FROM elections WHERE id = ?");
$stmt->execute([$election_id]);
$election = $stmt->fetch(PDO::FETCH_ASSOC);

// Get positions and votes
$stmt = $conn->prepare("
    SELECT p.id as position_id, p.position_name,
           c.id as candidate_id, c.full_name,
           COUNT(v.id) as vote_count
    FROM positions p
    LEFT JOIN candidates c ON p.id = c.position_id
    LEFT JOIN votes v ON c.id = v.candidate_id
    WHERE p.election_id = ?
    GROUP BY p.id, c.id
    ORDER BY p.position_order, c.full_name
");
$stmt->execute([$election_id]);

try {
    // Fetch election results
    $query = "SELECT 
                c.id as id,
                c.full_name as name,
                p.position_name as position,
                COUNT(v.id) as vote_count,
                c.photo as image_path
              FROM candidates c
              LEFT JOIN votes v ON c.id = v.candidate_id
              LEFT JOIN positions p ON c.position_id = p.id
              WHERE c.election_id = ?
              GROUP BY c.id
              ORDER BY p.position_order, vote_count DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->execute([$election_id]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total votes
    $total_votes_query = "SELECT COUNT(*) as total FROM votes WHERE election_id = ?";
    $stmt = $conn->prepare($total_votes_query);
    $stmt->execute([$election_id]);
    $total_votes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css">
    <style>
        .results-card {
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .results-card:hover {
            transform: translateY(-5px);
        }
        .candidate-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
        }
        .progress {
            height: 25px;
        }
    </style>
</head>
<body>
    <?php include('includes/admin_navbar.php'); ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col">
                <h2 class="text-primary mb-3">Election Results Dashboard</h2>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Total Votes Cast: <?php echo $total_votes; ?></h5>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $current_position = '';
        foreach ($result as $row) {
            if ($current_position != $row['position']) {
                if ($current_position != '') {
                    echo '</div>'; // Close previous position div
                }
                $current_position = $row['position'];
                echo '<h3 class="mt-4 mb-3">' . htmlspecialchars($current_position) . '</h3>';
                echo '<div class="row">';
            }
        ?>
            <div class="col-md-6 col-lg-4">
                <div class="card results-card shadow-sm">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <img src="../uploads/candidates/<?php echo htmlspecialchars($row['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                 class="candidate-image mb-2">
                            <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                        </div>
                        
                        <?php
                        $percentage = $total_votes > 0 ? ($row['vote_count'] / $total_votes) * 100 : 0;
                        ?>
                        
                        <div class="progress mb-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" 
                                 style="width: <?php echo $percentage; ?>%" 
                                 aria-valuenow="<?php echo $percentage; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <?php echo number_format($percentage, 1); ?>%
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <h6 class="mb-0">Total Votes: <?php echo $row['vote_count']; ?></h6>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        }
        if ($current_position != '') {
            echo '</div>'; // Close last position div
        }
        ?>

        <div class="row mt-4">
            <div class="col">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <canvas id="resultsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('resultsChart').getContext('2d');
        const resultsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [<?php 
                    $labels = array_map(function($row) {
                        return "'" . $row['name'] . " (" . $row['position'] . ")'";
                    }, $result);
                    echo implode(',', $labels);
                ?>],
                datasets: [{
                    label: 'Votes',
                    data: [<?php 
                        $votes = array_map(function($row) {
                            return $row['vote_count'];
                        }, $result);
                        echo implode(',', $votes);
                    ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html> 