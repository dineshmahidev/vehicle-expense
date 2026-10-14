<?php
/**
 * Driver Assignment System (Static & Dynamic Trips)
 * Shree Laxmi Expenses
 */

$page_title = 'Driver Assignments (Static & Dynamic)';
require_once __DIR__ . '/includes/header.php';
require_manager(); // Admin or Branch Manager

// Handle Assignment Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();

    // 1. Create Dynamic Trip Assignment
    if ($_POST['action'] === 'create_dynamic') {
        $lorry_id = (int)$_POST['lorry_id'];
        $driver_id = (int)$_POST['driver_id'];
        $branch_id = (int)$_POST['branch_id'];
        $trip_route = clean($_POST['trip_route']);
        $start_date = clean($_POST['start_date']);
        $end_date = !empty($_POST['end_date']) ? clean($_POST['end_date']) : null;
        $start_odometer = (int)$_POST['start_odometer'];
        $notes = clean($_POST['notes']);

        try {
            // Deactivate any existing active dynamic trip for this lorry
            $pdo->prepare("UPDATE driver_assignments SET status = 'completed', end_date = CURDATE() 
                           WHERE lorry_id = ? AND status = 'active' AND assignment_type = 'dynamic'")
                ->execute([$lorry_id]);

            // Insert new dynamic assignment
            $stmt = $pdo->prepare("INSERT INTO driver_assignments 
                (lorry_id, driver_id, branch_id, assignment_type, trip_route, start_date, end_date, start_odometer, status, notes, assigned_by) 
                VALUES (?, ?, ?, 'dynamic', ?, ?, ?, ?, 'active', ?, ?)");
            $stmt->execute([$lorry_id, $driver_id, $branch_id, $trip_route, $start_date, $end_date, $start_odometer, $notes, $user['id']]);

            set_flash('success', "Dynamic trip assignment created successfully!");
            header("Location: assignments.php");
            exit;
        } catch (\PDOException $e) {
            set_flash('danger', "Error assigning dynamic trip: " . $e->getMessage());
        }
    } 
    // 2. Complete / Close Dynamic Assignment
    elseif ($_POST['action'] === 'complete_dynamic') {
        $id = (int)$_POST['assignment_id'];
        $end_odometer = !empty($_POST['end_odometer']) ? (int)$_POST['end_odometer'] : null;

        try {
            $stmt = $pdo->prepare("UPDATE driver_assignments SET status = 'completed', end_date = CURDATE(), end_odometer = ? WHERE id = ?");
            $stmt->execute([$end_odometer, $id]);

            // Update lorry odometer if provided
            if ($end_odometer) {
                $pdo->prepare("UPDATE lorries l 
                               JOIN driver_assignments da ON da.lorry_id = l.id 
                               SET l.current_odometer = ? 
                               WHERE da.id = ?")->execute([$end_odometer, $id]);
            }

            set_flash('success', "Dynamic trip completed successfully! Lorry returned to default status.");
            header("Location: assignments.php");
            exit;
        } catch (\PDOException $e) {
            set_flash('danger', "Error completing trip: " . $e->getMessage());
        }
    }
    // 3. Update Static Default Driver
    elseif ($_POST['action'] === 'update_static') {
        $lorry_id = (int)$_POST['lorry_id'];
        $driver_id = !empty($_POST['driver_id']) ? (int)$_POST['driver_id'] : null;

        try {
            $stmt = $pdo->prepare("UPDATE lorries SET static_driver_id = ? WHERE id = ?");
            $stmt->execute([$driver_id, $lorry_id]);

            // Also keep record in assignments table
            if ($driver_id) {
                $branch_id = $pdo->query("SELECT branch_id FROM lorries WHERE id = $lorry_id")->fetchColumn();
                $pdo->prepare("INSERT INTO driver_assignments (lorry_id, driver_id, branch_id, assignment_type, start_date, status, notes, assigned_by)
                               VALUES (?, ?, ?, 'static', CURDATE(), 'active', 'Static default driver set', ?)")
                    ->execute([$lorry_id, $driver_id, $branch_id, $user['id']]);
            }

            set_flash('success', "Static default driver updated successfully!");
            header("Location: assignments.php");
            exit;
        } catch (\PDOException $e) {
            set_flash('danger', "Error updating static driver: " . $e->getMessage());
        }
    }
}

// Fetch Active Dynamic Trips
$stmt_dynamic = $pdo->query("SELECT da.*, l.lorry_number, l.model_name, u.name as driver_name, u.phone as driver_phone, b.branch_name
                             FROM driver_assignments da
                             JOIN lorries l ON da.lorry_id = l.id
                             JOIN users u ON da.driver_id = u.id
                             JOIN branches b ON da.branch_id = b.id
                             WHERE da.assignment_type = 'dynamic' AND da.status = 'active'
                             ORDER BY da.id DESC");
$active_dynamic_trips = $stmt_dynamic->fetchAll();

// Fetch Static Lorry Roster
$lorries_static = get_all_lorries($pdo);

// Fetch Completed / Past Assignments
$stmt_past = $pdo->query("SELECT da.*, l.lorry_number, u.name as driver_name, b.branch_name
                          FROM driver_assignments da
                          JOIN lorries l ON da.lorry_id = l.id
                          JOIN users u ON da.driver_id = u.id
                          JOIN branches b ON da.branch_id = b.id
                          WHERE da.assignment_type = 'dynamic' AND da.status = 'completed'
                          ORDER BY da.id DESC LIMIT 10");
$past_trips = $stmt_past->fetchAll();

$lorries = get_all_lorries($pdo);
$drivers = get_all_drivers($pdo);
$branches = get_all_branches($pdo);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Driver Fleet Assignments</h1>
        <p class="page-subtitle">Assign drivers statically (permanent default) or dynamically (route-specific trip shifts)</p>
    </div>

    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-primary btn-sm" onclick="openModal('addDynamicModal')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
            <span>+ New Dynamic Trip Assignment</span>
        </button>
        <button type="button" class="btn btn-outline btn-sm" onclick="openModal('updateStaticModal')">
            <span>Edit Static Defaults</span>
        </button>
    </div>
</div>

<!-- Active Dynamic Trip Assignments Card -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            <span>Active Dynamic Trips on the Road</span>
        </div>
        <span class="pill pill-dynamic"><?= count($active_dynamic_trips) ?> Active Dynamic Trips</span>
    </div>

    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Lorry No</th>
                        <th>Dynamic Driver</th>
                        <th>Trip Route</th>
                        <th>Start Date</th>
                        <th>Start Odometer</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($active_dynamic_trips)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:30px; color:#94a3b8;">
                                No dynamic trip assignments active currently. All lorries running on static default drivers.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($active_dynamic_trips as $dt): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:700; color:var(--primary); font-size:14px;"><?= clean($dt['lorry_number']) ?></div>
                                    <small style="color:#64748b;"><?= clean($dt['model_name']) ?></small>
                                </td>
                                <td>
                                    <div style="font-weight:700; color:#0f172a;"><?= clean($dt['driver_name']) ?></div>
                                    <small style="color:#64748b;"><?= clean($dt['driver_phone']) ?></small>
                                </td>
                                <td>
                                    <div style="font-weight:600; color:#b45309;"><?= clean($dt['trip_route']) ?></div>
                                    <?php if (!empty($dt['notes'])): ?>
                                        <small style="color:#64748b;"><?= clean($dt['notes']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= format_date($dt['start_date']) ?></td>
                                <td><?= number_format($dt['start_odometer']) ?> KM</td>
                                <td style="text-align:center;">
                                    <button type="button" class="btn btn-outline btn-sm" onclick="openCompleteTripModal(<?= $dt['id'] ?>, '<?= clean($dt['lorry_number']) ?>', '<?= clean($dt['driver_name']) ?>', <?= $dt['start_odometer'] ?>)">
                                        <span>Complete Trip</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Static Lorry Driver Roster -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            <span>Static Permanent Fleet Driver Roster</span>
        </div>
        <span class="pill pill-static">Default Assigned Drivers</span>
    </div>

    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Lorry Number</th>
                        <th>Branch Hub</th>
                        <th>Permanent Static Driver</th>
                        <th>Driver Phone</th>
                        <th>Current Operating State</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lorries_static as $ls): ?>
                        <tr>
                            <td>
                                <strong style="font-size:13.5px;"><?= clean($ls['lorry_number']) ?></strong>
                                <div style="font-size:11px; color:#64748b;"><?= clean($ls['model_name']) ?></div>
                            </td>
                            <td><?= clean($ls['branch_name']) ?></td>
                            <td>
                                <?php if (!empty($ls['static_driver_name'])): ?>
                                    <div style="font-weight:600;"><?= clean($ls['static_driver_name']) ?></div>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-style:italic;">No default static driver</span>
                                <?php endif; ?>
                            </td>
                            <td><?= clean($ls['static_driver_phone'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($ls['dynamic_driver_name'])): ?>
                                    <span class="pill pill-dynamic">Overridden by Dynamic Trip (<?= clean($ls['dynamic_driver_name']) ?>)</span>
                                <?php elseif (!empty($ls['static_driver_name'])): ?>
                                    <span class="pill pill-static">Running with Static Driver</span>
                                <?php else: ?>
                                    <span class="pill pill-idle">Idle (No Driver)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: New Dynamic Trip Assignment -->
<div class="modal-overlay" id="addDynamicModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">New Dynamic Trip Assignment</h3>
            <button type="button" class="modal-close" onclick="closeModal('addDynamicModal')">&times;</button>
        </div>
        <form method="POST" action="assignments.php">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="create_dynamic">

            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Select Lorry *</label>
                        <select name="lorry_id" class="form-select" required>
                            <option value="">-- Choose Lorry --</option>
                            <?php foreach ($lorries as $l): ?>
                                <option value="<?= $l['id'] ?>"><?= clean($l['lorry_number']) ?> (<?= clean($l['model_name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assign Dynamic Driver *</label>
                        <select name="driver_id" class="form-select" required>
                            <option value="">-- Choose Driver --</option>
                            <?php foreach ($drivers as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= clean($d['name']) ?> (<?= clean($d['phone']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Operating Branch Hub *</label>
                        <select name="branch_id" class="form-select" required>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= clean($b['branch_name']) ?> (<?= clean($b['city']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Trip Route / Corridor *</label>
                        <input type="text" name="trip_route" class="form-control" required placeholder="e.g. Salem -> Mumbai -> Delhi">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Trip Start Date *</label>
                        <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Expected Return Date</label>
                        <input type="date" name="end_date" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Start Odometer (KM)</label>
                    <input type="number" name="start_odometer" class="form-control" placeholder="Current vehicle KM reading">
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Trip Instructions / Consignee Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Container seal info, consignee instructions..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addDynamicModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Dispatch Dynamic Trip</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Complete Dynamic Trip -->
<div class="modal-overlay" id="completeTripModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">Complete Dynamic Trip</h3>
            <button type="button" class="modal-close" onclick="closeModal('completeTripModal')">&times;</button>
        </div>
        <form method="POST" action="assignments.php">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="complete_dynamic">
            <input type="hidden" name="assignment_id" id="complete_assignment_id">

            <div class="modal-body">
                <p style="font-size:13.5px; color:#475569; margin-bottom:16px;">
                    Closing trip for lorry <strong id="trip_modal_lorry"></strong> driven by <strong id="trip_modal_driver"></strong>.
                </p>

                <div class="form-group">
                    <label class="form-label">Final Trip Ending Odometer (KM)</label>
                    <input type="number" name="end_odometer" id="trip_modal_end_odometer" class="form-control" required placeholder="Enter ending KM">
                    <small style="color:#64748b;">Starting KM was: <span id="trip_modal_start_odo"></span> KM</small>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('completeTripModal')">Cancel</button>
                <button type="submit" class="btn btn-success">Confirm Trip Completed</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Update Static Default Driver -->
<div class="modal-overlay" id="updateStaticModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">Update Static Default Driver</h3>
            <button type="button" class="modal-close" onclick="closeModal('updateStaticModal')">&times;</button>
        </div>
        <form method="POST" action="assignments.php">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="update_static">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Select Lorry *</label>
                    <select name="lorry_id" class="form-select" required>
                        <?php foreach ($lorries as $l): ?>
                            <option value="<?= $l['id'] ?>"><?= clean($l['lorry_number']) ?> (<?= clean($l['model_name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Default Static Driver</label>
                    <select name="driver_id" class="form-select">
                        <option value="">-- Remove Static Driver (Unassigned) --</option>
                        <?php foreach ($drivers as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= clean($d['name']) ?> (<?= clean($d['phone']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('updateStaticModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Default Driver</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCompleteTripModal(id, lorryNo, driverName, startOdo) {
    document.getElementById('complete_assignment_id').value = id;
    document.getElementById('trip_modal_lorry').innerText = lorryNo;
    document.getElementById('trip_modal_driver').innerText = driverName;
    document.getElementById('trip_modal_start_odo').innerText = startOdo;
    document.getElementById('trip_modal_end_odometer').min = startOdo;
    document.getElementById('trip_modal_end_odometer').value = startOdo;
    openModal('completeTripModal');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
