<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireAdmin();

$pageTitle = 'User Management';
$auth = new Auth();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $result = $auth->createUser([
            'username'  => sanitize($_POST['username'] ?? ''),
            'email'     => sanitize($_POST['email'] ?? ''),
            'full_name' => sanitize($_POST['full_name'] ?? ''),
            'password'  => $_POST['password'] ?? '',
            'role'      => sanitize($_POST['role'] ?? 'agent'),
        ]);
        redirect('users.php', $result['success'] ? 'User created successfully!' : $result['message'], $result['success'] ? 'success' : 'error');
    }

    if ($action === 'update') {
        $result = $auth->updateUser(intval($_POST['user_id'] ?? 0), [
            'full_name' => sanitize($_POST['full_name'] ?? ''),
            'email'     => sanitize($_POST['email'] ?? ''),
            'role'      => sanitize($_POST['role'] ?? ''),
            'status'    => sanitize($_POST['status'] ?? ''),
            'password'  => !empty($_POST['password']) ? $_POST['password'] : null,
        ]);
        redirect('users.php', $result['success'] ? 'User updated!' : $result['message'], $result['success'] ? 'success' : 'error');
    }

    if ($action === 'delete') {
        $result = $auth->deleteUser(intval($_POST['user_id'] ?? 0));
        redirect('users.php', $result['success'] ? 'User deleted!' : $result['message'], $result['success'] ? 'success' : 'error');
    }
}

// Get all users
$users = $auth->getAllUsers();
$currentUser = Auth::user();

include __DIR__ . '/../includes/layout_header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 style="font-weight:700;color:var(--text-primary);">
        <i class="fas fa-users me-2" style="color:var(--accent-purple);"></i> User Management
    </h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="fas fa-user-plus me-2"></i> Add User
    </button>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="stat-card" style="--card-accent:rgba(59,130,246,.1);">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon mb-0" style="background:var(--gradient-blue);"><i class="fas fa-users"></i></div>
                <div>
                    <div class="stat-value" style="font-size:1.5rem;"><?= count($users) ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card" style="--card-accent:rgba(16,185,129,.1);">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon mb-0" style="background:var(--gradient-green);"><i class="fas fa-user-check"></i></div>
                <div>
                    <div class="stat-value" style="font-size:1.5rem;"><?= count(array_filter($users, fn($u) => $u['status'] === 'active')) ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card" style="--card-accent:rgba(139,92,246,.1);">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon mb-0" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);"><i class="fas fa-user-shield"></i></div>
                <div>
                    <div class="stat-value" style="font-size:1.5rem;"><?= count(array_filter($users, fn($u) => in_array($u['role'], ['admin','super_admin']))) ?></div>
                    <div class="stat-label">Admins</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2"></i>All Users</span>
        <input type="text" class="form-control form-control-sm" style="max-width:200px;"
               placeholder="Search users..." oninput="filterUsers(this.value)">
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" id="usersTable">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="user-avatar"><?= getInitials($user['full_name']) ?></div>
                                <span style="color:var(--text-primary);font-weight:500;"><?= sanitize($user['full_name']) ?></span>
                                <?php if ($user['id'] == $currentUser['id']): ?>
                                    <span class="badge badge-primary" style="font-size:.6rem;">You</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="color:var(--text-muted);">@<?= sanitize($user['username']) ?></td>
                        <td style="color:var(--text-muted);font-size:.85rem;"><?= sanitize($user['email']) ?></td>
                        <td><?= getStatusBadge($user['role']) ?></td>
                        <td><?= getStatusBadge($user['status']) ?></td>
                        <td style="color:var(--text-muted);font-size:.8rem;">
                            <?= $user['last_login'] ? timeAgo($user['last_login']) : 'Never' ?>
                        </td>
                        <td style="color:var(--text-muted);font-size:.8rem;"><?= formatDate($user['created_at'], 'd M Y') ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline btn-icon"
                                        onclick="editUser(<?= htmlspecialchars(json_encode($user), ENT_QUOTES) ?>)"
                                        title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($user['id'] != $currentUser['id']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-icon"
                                            onclick="return confirm('Delete this user?')"
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required placeholder="e.g. John Doe">
                    </div>
                    <div class="row g-3">
                        <div class="col">
                            <label class="form-label">Username *</label>
                            <input type="text" name="username" class="form-control" required placeholder="username">
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required placeholder="email@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required minlength="6"
                               placeholder="Minimum 6 characters">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus me-1"></i> Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" id="editUserId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="editFullName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="editEmail" class="form-control" required>
                    </div>
                    <div class="row g-3">
                        <div class="col">
                            <label class="form-label">Status</label>
                            <select name="status" id="editStatus" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label">New Password <small style="color:var(--text-muted);">(leave empty to keep current)</small></label>
                        <input type="password" name="password" class="form-control" minlength="6"
                               placeholder="Leave blank to keep current password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraJs = <<<'JSEOF'
<script>
function editUser(user) {
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editFullName').value = user.full_name;
    document.getElementById('editEmail').value = user.email;
    document.getElementById('editStatus').value = user.status;
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function filterUsers(val) {
    const q = val.toLowerCase();
    document.querySelectorAll('#usersTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
JSEOF;

include __DIR__ . '/../includes/layout_footer.php';
?>
