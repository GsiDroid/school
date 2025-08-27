<?php
/**
 * Gallery Management Module
 * Handles image gallery for the school
 */

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include common functions
require_once '../includes/functions.php';

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Set page title
$pageTitle = "Gallery Management";
$currentPage = "gallery";

// Get all gallery categories
$stmt = $conn->prepare("SELECT * FROM gallery_categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all gallery images with category names
$stmt = $conn->prepare("SELECT i.*, c.name as category_name 
                      FROM gallery_images i 
                      LEFT JOIN gallery_categories c ON i.category_id = c.id 
                      ORDER BY i.created_at DESC");
$stmt->execute();
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
    </ol>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-images me-1"></i>
                        Gallery Management
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-folder-plus me-1"></i> Add Category
                        </button>
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#uploadImagesModal">
                            <i class="fas fa-upload me-1"></i> Upload Images
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Category Filter -->
                    <div class="mb-4">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary active" data-category="all">All</button>
                            <?php foreach ($categories as $category): ?>
                                <button type="button" class="btn btn-outline-primary" data-category="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Gallery Grid -->
                    <div class="row gallery-container">
                        <?php if (empty($images)): ?>
                            <div class="col-12 text-center py-5">
                                <p class="text-muted">No images found. Upload some images to get started.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($images as $image): ?>
                                <div class="col-md-3 col-sm-6 mb-4 gallery-item" data-category="<?php echo $image['category_id']; ?>">
                                    <div class="card h-100">
                                        <a href="../uploads/gallery/<?php echo $image['file_path']; ?>" data-lightbox="gallery" data-title="<?php echo htmlspecialchars($image['title']); ?>">
                                            <img src="../uploads/gallery/<?php echo $image['file_path']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($image['title']); ?>" style="height: 200px; object-fit: cover;">
                                        </a>
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($image['title']); ?></h5>
                                            <p class="card-text small text-muted">
                                                <span class="badge bg-info"><?php echo htmlspecialchars($image['category_name'] ?? 'Uncategorized'); ?></span>
                                                <br>
                                                <small><?php echo date('M d, Y', strtotime($image['upload_date'])); ?></small>
                                            </p>
                                        </div>
                                        <div class="card-footer bg-transparent border-top-0 text-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-image" data-id="<?php echo $image['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Add Gallery Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCategoryForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="categoryName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="categoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="categoryDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Images Modal -->
<div class="modal fade" id="uploadImagesModal" tabindex="-1" aria-labelledby="uploadImagesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadImagesModalLabel">Upload Images</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadImagesForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="imageCategory" class="form-label">Category</label>
                        <select class="form-select" id="imageCategory" name="category_id">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="imageTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="imageTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="imageDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="imageDescription" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="galleryImages" class="form-label">Select Images</label>
                        <input type="file" class="form-control" id="galleryImages" name="images[]" accept="image/*" multiple required>
                        <small class="form-text text-muted">You can select multiple images. Max file size: 5MB per image.</small>
                    </div>
                    <div id="imagePreviewContainer" class="row mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteImageModal" tabindex="-1" aria-labelledby="deleteImageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteImageModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this image? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteImage">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Category filter
        const categoryButtons = document.querySelectorAll('[data-category]');
        const galleryItems = document.querySelectorAll('.gallery-item');
        
        categoryButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                categoryButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                const category = this.getAttribute('data-category');
                
                // Show/hide gallery items based on category
                galleryItems.forEach(item => {
                    if (category === 'all' || item.getAttribute('data-category') === category) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
        
        // Image preview for upload
        document.getElementById('galleryImages').addEventListener('change', function(e) {
            const previewContainer = document.getElementById('imagePreviewContainer');
            previewContainer.innerHTML = '';
            
            const files = e.target.files;
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                
                if (!file.type.match('image.*')) {
                    continue;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const previewCol = document.createElement('div');
                    previewCol.className = 'col-md-3 mb-2';
                    
                    const previewImg = document.createElement('img');
                    previewImg.src = e.target.result;
                    previewImg.className = 'img-thumbnail';
                    previewImg.style.height = '150px';
                    previewImg.style.objectFit = 'cover';
                    
                    previewCol.appendChild(previewImg);
                    previewContainer.appendChild(previewCol);
                };
                
                reader.readAsDataURL(file);
            }
        });
        
        // Delete image
        let imageIdToDelete = null;
        
        document.querySelectorAll('.delete-image').forEach(button => {
            button.addEventListener('click', function() {
                imageIdToDelete = this.getAttribute('data-id');
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteImageModal'));
                deleteModal.show();
            });
        });
        
        document.getElementById('confirmDeleteImage').addEventListener('click', function() {
            if (imageIdToDelete) {
                // Send AJAX request to delete image
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'delete_image.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Reload page or remove element from DOM
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }
                };
                xhr.send('id=' + imageIdToDelete);
                
                // Close modal
                const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteImageModal'));
                deleteModal.hide();
            }
        });
        
        // Add category form submission
        document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'add_category.php', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Reload page or update DOM
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                }
            };
            xhr.send(formData);
        });
        
        // Upload images form submission
        document.getElementById('uploadImagesForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'upload_images.php', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Reload page or update DOM
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                }
            };
            xhr.send(formData);
        });
    });
</script>

<?php include_once '../includes/footer.php'; ?>