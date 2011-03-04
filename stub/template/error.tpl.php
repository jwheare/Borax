<!DOCTYPE html>

<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title><?php out($title . " | " . SITE_NAME); ?></title>
    </head>
    <body>
        <h2><?php out($title); ?></h2>
        <?php if ($message): ?>
            <p><?php echo linkify(safe($message)); ?></p>
        <?php endif; ?>
    </body>
</html>
