<?php $this->out('header.inc'); ?>
<?php if ($this->session->isLoggedIn()): ?>
<?php dump($this->user); ?>
<?php else: ?>
<a href="<?php out($this->session->addNext('/twitter/start', $this->request->get('next'))); ?>">Sign in with Twitter</a>
<?php endif; ?>
<?php $this->out('footer.inc'); ?>