<!DOCTYPE html>

<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title><?php
        if (isset($title)):
            out($title . " | " . SITE_NAME);
        else:
            out(SITE_NAME);
        endif;
        ?></title>
        <link rel="stylesheet" href="/main.css" type="text/css" media="screen">
        <?php if (isset($shorter)): ?>
        <link rev="canonical" href="<?php out($shorter); ?>">
        <?php endif; ?>
        <?php if (isset($cssInclude)) echo $cssInclude; ?>
        <?php if (isset($description)): ?>
        <meta name="description" content="<?php out($description); ?>">
        <?php endif; ?>
    </head>
    <body>
        <div id="content">
