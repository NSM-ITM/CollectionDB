<nav id="section-nav" class="navigation vertical">
<?php
    $navArray = array(
        array(
            'label' => 'Loaned Items',
            'action' => 'browse-loaned',
            'module' => 'item-loans',
        ),
        array(
            'label' => 'Repairing Items',
            'action' => 'browse-repairing',
            'module' => 'item-loans',
        ),
        array(
            'label' => 'Missing Items',
            'action' => 'browse-missing',
            'module' => 'item-loans',
        ),
    );
    echo nav($navArray, 'admin_navigation_settings');
?>
</nav>