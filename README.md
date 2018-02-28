# Patchwork 2.1

    composer require antecedent/patchwork

This library allows any function/method call to be intercepted. It does not depend on any core extensions.

Here, the internally preferred jargon for this behavior is **function redefinition**, since this conceptualization is already familiar from Runkit and from related techniques in other languages. The two viewpoints are mostly equivalent from the perspective of a developer using Patchwork. **Monkey-patching** is yet another synonym.

## Example of testing-related usage

```php
use function Patchwork\{redefine, restoreAll, always};

final class ImportServiceTest extends PHPUnit\Framework\TestCase
{
    private $importService; // Assume a proper setUp()
    private $calls = [];    // Assume a logCall() method to populate this

    public function testNoImportOccursIfDataRecent()
    {
        redefine('file_get_contents', [$this, 'logCall']);
        redefine('filemtime', always(strtotime('-2 days')));
        $this->importService->import();
        $this->assertEmpty($this->calls);
    }

    public function tearDown()
    {
        restoreAll();
        $this->calls = [];
    }
}
```
