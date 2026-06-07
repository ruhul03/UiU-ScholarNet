<?php

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

if (isset($_SESSION['error'])): ?>
    <div class="alert-error-editor alert-spacing">
        <i class="fa-solid fa-circle-exclamation"></i> 
        <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']); 
        ?>
    </div>
<?php endif; ?>
