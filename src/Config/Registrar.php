<?php

declare(strict_types=1);

namespace Daycry\TaxId\Config;

/**
 * CI4 Registrar hook for `daycry/taxid`.
 *
 * `Config\Modules::$discoverInComposer` (on by default) makes CI4 discover this
 * class from the `Daycry\TaxId\` namespace, exactly as it discovers
 * `Config\Services`. `service('taxid')` and the `taxid:*` commands already work
 * through that same mechanism without this class.
 *
 * It deliberately declares **no** `Autoload()` method. CI4 only merges Registrar
 * hooks into config classes extending `CodeIgniter\Config\BaseConfig`, and
 * `Config\Autoload` does not extend it — such a hook would never fire.
 *
 * The bundled helper loads on demand instead: call `helper('taxid')` and CI4's
 * locator finds `src/Helpers/taxid_helper.php` through this package's PSR-4
 * mapping. An app wanting it always-on can add `'taxid'` to its own
 * `Config\Autoload::$helpers`.
 *
 * @see \Daycry\TaxId\Config\Services
 * @see src/Helpers/taxid_helper.php
 */
class Registrar
{
}
