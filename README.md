# Patchwork 2.1

    composer require antecedent/patchwork

This library allows any function/method call to be intercepted, as if the function were redefined at runtime. It does not depend on any core extensions.

## Example of testing-related usage

Patchwork is used primarily to ease the testing of legacy code.

Suppose that a hypothetical `ImportService` imports a remote resource using `file_get_contents()`, unless the local copy is sufficiently recent. The latter check is achieved using `filemtime()`.

It follows that these functions are stubborn dependencies of `ImportService`, at least if they are called the usual way, that is, `file_get_contents($path)` and not `($this->fileReader)($path)`.

What makes these dependencies stubborn is that they cannot be displaced using any conventional runtime strategies. Concretely, if `ImportService` calls `file_get_contents()`, then there is no non-invasive way of making it call something else instead for testing purposes.

It is easy to avoid this issue and do without Patchwork, as in `function __construct($fileReader = 'file_get_contents')`. However, when presented with a legacy codebase where it is impractical to perform such refactoring, Patchwork will help:

```php
use function Patchwork\{redefine, restoreAll, always, getFunction};

final class ImportServiceTest extends PHPUnit\Framework\TestCase
{
    private $importService = new ImportServiceTest; // FIXME
    private $calls = [];    

    public function testNoImportOccursIfDataRecent()
    {
        redefine('file_get_contents', [$this, 'logCall']);
        redefine('filemtime', always(strtotime('-2 days')));
        $this->calls = [];
        $this->importService->import();
        $this->assertEmpty($this->calls);
        restoreAll();
    }

    public function logCall()
    {
        $this->calls[] = Patchwork\getFunction();
    }
}
```
