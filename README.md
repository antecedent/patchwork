# Patchwork 2.0

```php
use function Patchwork\{redefine, always};
redefine('time', always(strtotime('Dec 31, 1999')));
echo time(); # => 946598400
```
