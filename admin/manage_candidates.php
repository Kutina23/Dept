<?php
require_once '../config/database.php';

// Replace the direct session_start() with a check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simplify the session check
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../admin_login.php');
    exit();
}

// Get election_id from URL parameter or set default
$election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;

// If no election_id is provided, get the most recent election
if ($election_id === 0) {
    $stmt = $conn->query("SELECT id FROM elections ORDER BY created_at DESC LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $election_id = $result ? $result['id'] : 0;
}

// Verify election exists
if ($election_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM elections WHERE id = ?");
    $stmt->execute([$election_id]);
    $current_election = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_election) {
        $_SESSION['error'] = "Election not found";
        header('Location: dashboard.php');
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_candidate'])) {
        $full_name = $_POST['full_name'];
        $student_id = $_POST['student_id'];
        $position_id = $_POST['position_id'];
        $election_id = $_POST['election_id'];
        
        // Handle photo upload
        $photo = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/candidates/';
            $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photo = uniqid() . '.' . $file_extension;
            move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo);
        }
        
        $manifesto = $_POST['manifesto'];
        
        $stmt = $conn->prepare("INSERT INTO candidates (full_name, student_id, position_id, election_id, photo, manifesto) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $student_id, $position_id, $election_id, $photo, $manifesto]);
        
        $_SESSION['success'] = "Candidate added successfully!";
        header('Location: manage_candidates.php');
        exit();
    }
    
    if (isset($_POST['delete_candidate'])) {
        $candidate_id = $_POST['candidate_id'];
        $stmt = $conn->prepare("DELETE FROM candidates WHERE candidate_id = ?");
        $stmt->execute([$candidate_id]);
        
        $_SESSION['success'] = "Candidate deleted successfully!";
        header('Location: manage_candidates.php');
        exit();
    }
}

// Fetch all positions
$stmt = $conn->prepare("SELECT id AS position_id, position_name FROM positions WHERE election_id = ? ORDER BY position_order");
$stmt->execute([$election_id]);
$positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all candidates with their positions
$stmt = $conn->prepare("
    SELECT c.id AS candidate_id, c.full_name, c.student_id, c.photo, c.manifesto,
           p.position_name, p.id AS position_id, c.election_id
    FROM candidates c 
    JOIN positions p ON c.position_id = p.id 
    WHERE c.election_id = ?
    ORDER BY p.position_order, c.full_name
");
$stmt->execute([$election_id]);
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch active elections
$stmt = $conn->query("SELECT id, title FROM elections WHERE status != 'completed' ORDER BY created_at DESC");
$elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates | Election Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Top Navigation -->
        <nav class="top-nav">
            <div class="nav-brand">
                <i class="fas fa-vote-yea"></i>
                <span>Election System</span>
            </div>
            <div class="nav-profile">
                <span>Admin</span>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </nav>

        <!-- Side Navigation -->
        <nav class="side-nav">
            <a href="dashboard.php">
                <i class="fas fa-dashboard"></i>
                <span>Dashboard</span>
            </a>
            <a href="manage_candidates.php" class="active">
                <i class="fas fa-users"></i>
                <span>Candidates</span>
            </a>
            <a href="view_results.php">
                <i class="fas fa-chart-bar"></i>
                <span>Results</span>
            </a>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <div>
                    <h1>Manage Candidates</h1>
                    <p>Add and manage election candidates</p>
                </div>
                <button class="btn-add" onclick="toggleAddForm()">
                    <i class="fas fa-plus"></i> New Candidate
                </button>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Add Candidate Form -->
            <div class="modal" id="addCandidateForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2><i class="fas fa-user-plus"></i> Add New Candidate</h2>
                        <button class="close-btn" onclick="toggleAddForm()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form action="" method="POST" enctype="multipart/form-data" class="add-candidate-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="full_name">
                                        <i class="fas fa-user"></i> Full Name
                                    </label>
                                    <input type="text" id="full_name" name="full_name" 
                                           placeholder="Enter candidate's full name" required>
                                </div>
                                <div class="form-group">
                                    <label for="student_id">
                                        <i class="fas fa-id-card"></i> Student ID
                                    </label>
                                    <input type="text" id="student_id" name="student_id" 
                                           placeholder="Enter student ID" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="election_id">
                                        <i class="fas fa-vote-yea"></i> Election
                                    </label>
                                    <select id="election_id" name="election_id" required>
                                        <option value="">Select Election</option>
                                        <?php foreach ($elections as $election): ?>
                                            <option value="<?php echo $election['id']; ?>">
                                                <?php echo htmlspecialchars($election['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="position_id">
                                        <i class="fas fa-user-tie"></i> Position
                                    </label>
                                    <select id="position_id" name="position_id" required>
                                        <option value="">Select Position</option>
                                        <?php foreach ($positions as $position): ?>
                                            <option value="<?php echo $position['position_id']; ?>">
                                                <?php echo htmlspecialchars($position['position_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group photo-upload">
                                <label for="photo">
                                    <i class="fas fa-camera"></i> Candidate Photo
                                </label>
                                <div class="file-input-wrapper">
                                    <input type="file" id="photo" name="photo" 
                                           accept="image/*" required class="file-input">
                                    <div class="file-input-preview">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <span>Click to upload photo</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="manifesto">
                                    <i class="fas fa-file-alt"></i> Manifesto
                                </label>
                                <textarea id="manifesto" name="manifesto" rows="4" 
                                          placeholder="Enter candidate's manifesto" required></textarea>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="toggleAddForm()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <button type="submit" name="add_candidate" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add Candidate
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Candidates List -->
            <div class="candidates-wrapper">
                <?php
                $current_position = '';
                foreach ($candidates as $candidate):
                    if ($current_position !== $candidate['position_name']):
                        $current_position = $candidate['position_name'];
                ?>
                    <div class="position-header">
                        <h2><?php echo $current_position; ?></h2>
                    </div>
                <?php endif; ?>
                    <div class="candidate-tile">
                        <div class="candidate-info">
                            <?php if ($candidate['photo']): ?>
                                <img src="../uploads/candidates/<?php echo $candidate['photo']; ?>" 
                                     alt="<?php echo htmlspecialchars($candidate['full_name']); ?>"
                                     class="candidate-img">
                            <?php endif; ?>
                            <div class="candidate-details">
                                <h3><?php echo htmlspecialchars($candidate['full_name']); ?></h3>
                                <span class="id-badge">
                                    ID: <?php echo htmlspecialchars($candidate['student_id']); ?>
                                </span>
                                <p class="manifesto">
                                    <?php echo nl2br(htmlspecialchars($candidate['manifesto'])); ?>
                                </p>
                            </div>
                        </div>
                        <div class="candidate-actions">
                            <form action="" method="POST" class="delete-form">
                                <input type="hidden" name="candidate_id" 
                                       value="<?php echo $candidate['candidate_id']; ?>">
                                <button type="submit" name="delete_candidate" 
                                        class="btn-delete"
                                        onclick="return confirm('Delete this candidate?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f5f6fa;
            color: #2d3436;
            line-height: 1.6;
        }

        /* Dashboard Layout */
        .dashboard-container {
            display: grid;
            grid-template-areas:
                "top-nav top-nav"
                "side-nav main-content";
            grid-template-columns: 240px 1fr;
            grid-template-rows: 60px 1fr;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Top Navigation */
        .top-nav {
            grid-area: top-nav;
            background: #fff;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            position: fixed;
            width: 100%;
            height: 60px;
            z-index: 100;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3436;
        }

        .nav-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Side Navigation */
        .side-nav {
            grid-area: side-nav;
            background: #fff;
            padding: 2rem 0;
            border-right: 1px solid #e1e4e8;
            position: fixed;
            width: 240px;
            height: calc(100vh - 60px);
            top: 60px;
            overflow-y: auto;
        }

        .side-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: #2d3436;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .side-nav a.active {
            background: #f5f6fa;
            color: #3498db;
            border-left: 3px solid #3498db;
        }

        /* Main Content */
        .main-content {
            grid-area: main-content;
            padding: 2rem;
            margin-top: 60px;
            margin-left: 240px;
            overflow-y: auto;
            height: calc(100vh - 60px);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 1.75rem;
            color: #2d3436;
        }

        .page-header p {
            color: #636e72;
            margin-top: 0.25rem;
        }

        /* Candidate Tiles */
        .candidates-wrapper {
            display: grid;
            gap: 1.5rem;
        }

        .position-header {
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e1e4e8;
        }

        .candidate-tile {
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            transition: transform 0.2s ease;
        }

        .candidate-tile:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.08);
        }

        .candidate-info {
            display: flex;
            gap: 1.5rem;
            flex: 1;
        }

        .candidate-img {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
        }

        .candidate-details h3 {
            margin-bottom: 0.5rem;
            color: #2d3436;
        }

        .id-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #f5f6fa;
            border-radius: 4px;
            font-size: 0.875rem;
            color: #636e72;
            margin-bottom: 0.75rem;
        }

        /* Buttons */
        .btn-add {
            padding: 0.75rem 1.5rem;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: background 0.2s ease;
        }

        .btn-add:hover {
            background: #2980b9;
        }

        .btn-delete {
            padding: 0.5rem;
            background: #ff7675;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn-delete:hover {
            background: #d63031;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background: #fff;
            width: 90%;
            max-width: 550px;
            margin: 4rem auto;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            position: relative;
            max-height: calc(100vh - 8rem);
            overflow-y: auto;
        }

        .modal-header {
            background: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #e1e4e8;
            border-radius: 8px 8px 0 0;
        }

        .modal-header h2 {
            color: #2d3436;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #636e72;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .close-btn:hover {
            background: #e1e4e8;
            color: #2d3436;
        }

        .modal-body {
            padding: 1.5rem;
        }

        /* Form Styles */
        .add-candidate-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            color: #2d3436;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.625rem;
            border: 1px solid #e1e4e8;
            border-radius: 6px;
            font-size: 0.9375rem;
            transition: all 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
        }

        .photo-upload {
            border: 2px dashed #e1e4e8;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }

        .file-input-wrapper {
            position: relative;
            cursor: pointer;
        }

        .file-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-input-preview {
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 6px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
        }

        .file-input-preview i {
            font-size: 1.5rem;
            color: #3498db;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }

        .btn {
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            font-size: 0.9375rem;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-secondary {
            background: #e1e4e8;
            color: #2d3436;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .dashboard-container {
                grid-template-columns: 1fr;
                grid-template-areas:
                    "top-nav"
                    "main-content";
            }

            .side-nav {
                display: none;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .candidate-info {
                flex-direction: column;
            }

            .candidate-img {
                width: 60px;
                height: 60px;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .modal-content {
                width: 95%;
                margin: 1rem auto;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .modal-body {
                padding: 1rem;
            }
        }

        /* Textarea specific */
        #manifesto {
            height: 80px;
            resize: vertical;
            min-height: 60px;
            max-height: 150px;
        }
    </style>

    <script>
        function toggleAddForm() {
            const modal = document.getElementById('addCandidateForm');
            modal.style.display = modal.style.display === 'none' ? 'block' : 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addCandidateForm');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        document.getElementById('photo').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                const preview = this.parentElement.querySelector('.file-input-preview');
                preview.innerHTML = `
                    <i class="fas fa-check-circle"></i>
                    <span>${fileName}</span>
                `;
            }
        });

        // Add this new function to fetch positions
        async function fetchPositions(electionId) {
            try {
                const response = await fetch(`get_positions.php?election_id=${electionId}`);
                const positions = await response.json();
                
                const positionSelect = document.getElementById('position_id');
                positionSelect.innerHTML = '<option value="">Select Position</option>';
                
                positions.forEach(position => {
                    const option = document.createElement('option');
                    option.value = position.position_id;
                    option.textContent = position.position_name;
                    positionSelect.appendChild(option);
                });
            } catch (error) {
                console.error('Error fetching positions:', error);
            }
        }

        // Add event listener for election select
        document.getElementById('election_id').addEventListener('change', function() {
            if (this.value) {
                fetchPositions(this.value);
            }
        });
    </script>
</body>
</html> 