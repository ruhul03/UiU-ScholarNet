<?php
// Display success alert if it exists in the session
if (isset($_SESSION['success'])): ?>
    <div class="alert-success-editor alert-spacing">
        <i class="fa-solid fa-circle-check"></i> 
        <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']); 
        ?>
    </div>
<?php endif; ?>

<?php
// Display error alert if it exists in the session
if (isset($_SESSION['error'])): ?>
    <div class="alert-error-editor alert-spacing">
        <i class="fa-solid fa-circle-exclamation"></i> 
        <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']); 
        ?>
    </div>
<?php endif; ?>
