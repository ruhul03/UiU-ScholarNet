<?php
require_once('../includes/auth_check.php');
require_once('../includes/layout.php');
require_once('../includes/csrf.php');

// Determine sort mode from GET parameter (whitelist)
$allowed_sorts = ['reputation', 'department', 'specialty'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sorts) ? $_GET['sort'] : 'reputation';

switch ($sort) {
    case 'department':
        $order_clause = "department ASC, reputation DESC";
        break;
    case 'specialty':
        $order_clause = "skills ASC, reputation DESC";
        break;
    default:
        $order_clause = "reputation DESC, points DESC";
        break;
}

// Fetch faculty users with chosen sort
$facultyRes = db_query("SELECT id, full_name, role, department, skills, points, reputation FROM users WHERE role = 'faculty' ORDER BY $order_clause", [], "");

$ranked_faculty = [];
while ($row = $facultyRes->fetch_assoc()) {
    $ranked_faculty[] = $row;
}

layout_header("Faculty Supervisors | UIU ScholarNet", ["../assets/css/supervisors.css"]);
?>

    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content">
        <?php include('../includes/header.php'); ?>

        <div class="supervisors-container">
            <section class="supervisors-headline">
                <p>Faculty & Supervisor Rankings</p>
                <h1>Academic Supervisors</h1>
            </section>

            <div class="supervisors-sort-bar">
                <label for="sort-select"><i class="fa-solid fa-arrow-down-short-wide"></i> Sort by</label>
                <select id="sort-select" onchange="window.location.href='supervisors.php?sort='+this.value">
                    <option value="reputation" <?php echo $sort === 'reputation' ? 'selected' : ''; ?>>Reputation</option>
                    <option value="department" <?php echo $sort === 'department' ? 'selected' : ''; ?>>Department</option>
                    <option value="specialty" <?php echo $sort === 'specialty' ? 'selected' : ''; ?>>Specialty</option>
                </select>
            </div>

            <div class="supervisors-section">
                <table class="supervisors-table">
                    <thead>
                        <tr>
                            <th class="th-rank-w">Rank</th>
                            <th>Faculty Name</th>
                            <th>Department</th>
                            <th>Specialty (Skills)</th>
                            <th class="th-rep-w" style="text-align: right;">Reputation</th>
                            <th class="th-points-w" style="text-align: right;">Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = count($ranked_faculty);
                        if ($count > 0):
                            for ($i = 0; $i < $count; $i++):
                                $user = $ranked_faculty[$i];
                                
                                // Format skills
                                $skills_display = htmlspecialchars($user['skills'] ?? 'Not specified');
                        ?>
                            <tr>
                                <td class="rank-number">#<?php echo ($i + 1); ?></td>
                                <td class="user-name-col">
                                    <div class="name-wrapper">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=random&color=fff&bold=true" class="faculty-avatar" alt="Avatar">
                                        <a href="#" class="user-profile-trigger" data-user-id="<?php echo $user['id']; ?>" style="color: inherit; text-decoration: none; font-weight: 500; border-bottom: 1px dashed #ccc; padding-bottom: 2px; transition: opacity 0.2s;" onmouseover="this.style.opacity=0.7" onmouseout="this.style.opacity=1"><?php echo htmlspecialchars($user['full_name']); ?></a>
                                    </div>
                                </td>
                                <td class="table-department"><?php echo htmlspecialchars($user['department']); ?></td>
                                <td class="table-skills" title="<?php echo $skills_display; ?>"><?php echo $skills_display; ?></td>
                                <td class="table-rep-col"><?php echo number_format($user['reputation']); ?></td>
                                <td class="table-points-col"><?php echo number_format($user['points']); ?></td>
                            </tr>
                        <?php 
                            endfor;
                        else:
                        ?>
                            <tr>
                                <td colspan="6" class="td-empty-ranked">No faculty members found in the system yet.</td>
                            </tr>
                        <?php 
                        endif; 
                        ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

<?php layout_footer(); ?>
