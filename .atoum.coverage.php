<?php

use \mageekguy\atoum;

// Write all on stdout.
$stdOutWriter = new atoum\writers\std\out();

// Generate a CLI report.
$cliReport = new atoum\reports\realtime\cli();
$cliReport->addWriter($stdOutWriter);

// Coverage
$coverageField = new atoum\report\fields\runner\coverage\html('Clearbricks', '/var/www/coverage/clearbricks');
$coverageField->setRootUrl('http://localhost/coverage/clearbricks');
$cliReport->addField($coverageField);

$runner->addReport($cliReport);
