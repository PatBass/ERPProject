<?php
$container->setParameter('be2bill.sandbox', boolval(getenv('SYMFONY__BE2BILL__SANDBOX')));
$container->setParameter('klikandpay.sandbox', boolval(getenv('SYMFONY__KLIKANDPAY__SANDBOX')));
$container->setParameter('myastro.prefix', getenv('SYMFONY__MYASTRO__PREFIX'));
