Version 3.0.0-dev
-----------------

### Added

* The `InlineHTML` node now has an `hasLeadingNewline` attribute, that specifies whether the
  preceding closing tag contained a newline. The pretty printer honors this attribute.

Version 3.0.0-alpha1 (2016-07-25)
---------------------------------

### Added

* [7.1] Added support for `void` and `iterable` types. These will now be represented as strings
  (instead of `Name` instances) similar to other builtin types.
* [7.1] Added support for class constant visibility. The `ClassConst` node now has a `flags` subnode
  holding the visibility modifier, as well as `isPublic()`, `isProtected()` and `isPrivate()`
  methods. The constructor changed to accept the additional subnode.
* [7.1] Added support for nullable types. These are represented using a new `NullableType` node
  with a single `type` subnode.
* [7.1] Added support for short array destructuring syntax. This means that `Array` nodes may now
  appear as the left-hand-side of assignments and foreach value targets. Additionally the array
  items may now contain `null` values if elements are skipped.
* [7.1] Added support for keys in list() destructuring. The `List` subnode `vars` has been renamed
  to `items` and now contains `ArrayItem`s instead of plain variables.
* [7.1] Added support for multi-catch. The `Catch` subnode `type` has been renamed to `types` and
  is now an array of `Name`s.
* `Name::slice()` now supports lengths and negative offsets. This brings it in line with
  `array_slice()` functionality.

### Changed

Due to PHP 7.1 support additions described above, the node structure changed as follows:

* `void` and `iterable` types are now stored as strings if the PHP 7 parser is used.
* The `ClassConst` constructor changed to accept an additional `flags` subnode.
* The `Array` subnode `items` may now contain `null` elements (destructuring).
* The `List` subnode `vars` has been renamed to `items` and now contains `ArrayItem`s instead of
  plain variables.
* The `Catch` subnode `type` has been renamed to `types` and is now an array of `Name`s.

Additionally the following changes were made:

* The `type` subnode on `Class`, `ClassMethod` and `Property` has been renamed to `flags`. The
  `type` subnode has retained for backwards compatibility and is populated to the same value as
  `flags`. However, writes to `type` will not update `flags`.
* The `TryCatch` subnode `finallyStmts` has been replaced with a `finally` subnode that holds an
  explicit `Finally` node. This allows for more accurate attribute assignment.
* The `Trait` constructor now has the same form as the `Class` and `Interface` constructors: It
  takes an array of subnodes. Unlike classes/interfaces, traits can only have a `stmts` subnode.
* The `NodeDumper` now prints class/method/property/constant modifiers, as well as the include and
  use type in a textual representation, instead of only showing the number.
* All methods on `PrettyPrinter\Standard` are now protected. Previoulsy most of them were public.

### Removed

* Removed support for running on PHP 5.4. It is however still possible to parse PHP 5.2-5.4 code
  while running on a newer version.
* The deprecated `Comment::setLine()` and `Comment::setText()` methods have been removed.
* The deprecated `Name::set()`, `Name::setFirst()` and `Name::setLast()` methods have been removed.

Version 2.1.0 (2016-04-19)
--------------------------

### Fixed

* Properly support `B""` strings (with uppercase `B`) in a number of places.
* Fixed reformatting of indented parts in a certain non-standard comment style.

### Added

* Added `dumpComments` option to node dumper, to enable dumping of comments associated with nodes.
* Added `Stmt\Nop` node, that is used to collect comments located at the end of a block or at the
  end of a file (without a following node with which they could otherwise be associated).
* Added `kind` attribute to `Expr\Exit` to distinguish between `exit` and `die`.
* Added `kind` attribute to `Scalar\LNumber` to distinguish between decimal, binary, octal and
  hexadecimal numbers.
* Added `kind` attribtue to `Expr\Array` to distinguish between `array()` and `[]`.
* Added `kind` attribute to `Scalar\String` and `Scalar\Encapsed` to distinguish between
  single-quoted, double-quoted, heredoc and nowdoc string.
* Added `docLabel` attribute to `Scalar\String` and `Scalar\Encapsed`, if it is a heredoc or
  nowdoc string.
* Added start file offset information to `Comment` nodes.
* Added `setReturnType()` method to function and method builders.
* Added `-h` and `--help` options to `php-parse` script.

### Changed

* Invalid octal literals now throw a parse error in PHP 7 mode.
* The pretty printer takes all the new attributes mentioned in the previous section into account.
* The protected `AbstractPrettyPrinter::pComments()` method no longer returns a trailing newline.
* The bundled autoloader supports library files being stored in a different directory than
  `PhpParser` for easier downstream distribution.

### Deprecated

* The `Comment::setLine()` and `Comment::setText()` methods have been deprecated. Construct new
  objects instead.

### Removed

* The internal (but public) method `Scalar\LNumber::parse()` has been removed. A non-internal
  `LNumber::fromString()` method has been added instead.

Version 2.0.1 (2016-02-28)
--------------------------

### Fixed

* `declare() {}` and `declare();` are not semantically equivalent and will now result in different
  ASTs. The format case will have an empty `stmts` array, while the latter will set `stmts` to
  `null`.
* Magic constants are now supported as semi-reserved keywords.
* A shebang line like `#!/usr/bin/env php` is now allowed at the start of a namespaced file.
  Previously this generated an exception.
* The `prettyPrintFile()` method will not strip a trailing `?>` from the raw data that follows a
  `__halt_compiler()` statement.
* The `prettyPrintFile()` method will not strip an opening `<?php` if the file starts with a
  comment followed by InlineHTML.

Version 2.0.0 (2015-12-04)
--------------------------

### Changed

* String parts of encapsed strings are now represented using `Scalar\EncapsStringPart` nodes.
  Previously raw strings were used. This affects the `parts` child of `Scalar\Encaps` and
  `Expr\ShellExec`. The change has been done to allow assignment of attributes to encapsed string
  parts.

Version 2.0.0-beta1 (2015-10-21)
--------------------------------

### Fixed

* Fixed issue with too many newlines being stripped at the end of heredoc/nowdoc strings in some
  cases. (#227)

### Changed

* Update group use support to be in line with recent PHP 7.0 builds.
* Renamed `php-parse.php` to `php-parse` and registered it as a composer bin.
* Use composer PSR-4 autoloader instead of custom autoloader.
* Specify phpunit as a dev dependency.

### Added

* Added `shortArraySyntax` option to pretty printer, to print all arrays using short syntax.

Version 2.0.0-alpha1 (2015-07-14)
---------------------------------

A more detailed description of backwards incompatible changes can be found in the
[upgrading guide](UPGRADE-2.0.md).

### Removed

* Removed support for running on PHP 5.3. It is however still possible to parse PHP 5.2 and PHP 5.3
  code while running on a newer version.
* Removed legacy class name aliases. This includes the old non-namespaced class names and the old
  names for classes that were renamed for PHP 7 compatibility.
* Removed support for legacy node format. All nodes must have a `getSubNodeNames()` method now.

### Added

* Added support for remaining PHP 7 features that were not present in 1.x:
  * Group use declarations. These are represented using `Stmt\GroupUse` nodes. Furthermore a `type`
    attribute was added to `Stmt\UseUse` to handle mixed group use declarations.
  * Uniform variable syntax.
  * Generalized yield operator.
  * Scalar type declarations. These are presented using `'bool'`, `'int'`, `'float'` and `'string'`
    as the type. The PHP 5 parser also accepts these, however they'll be `Name` instances there.
  * Unicode escape sequences.
* Added `PhpParser\ParserFactory` class, which should be used to create parser instances.
* Added `Name::concat()` which concatenates two names.
* Added `Name->slice()` which takes a subslice of a name.

### Changed

* `PhpParser\Parser` is now an interface, implemented by `Parser\Php5`, `Parser\Php7` and
  `Parser\Multiple`. The `Multiple` parser will try multiple parsers, until one succeeds.
* Token constants are now defined on `PhpParser\Parser\Tokens` rather than `PhpParser\Parser`.
* The `Name->set()`, `Name->append()`, `Name->prepend()` and `Name->setFirst()` methods are
  deprecated in favor of `Name::concat()` and `Name->slice()`.
* The `NodeTraverser` no longer clones nodes by default. The old behavior can be restored by
  passing `true` to the constructor.
* The constructor for `Scalar` nodes no longer has a default value. E.g. `new LNumber()` should now
  be written as `new LNumber(0)`.

---

**This changelog only includes changes from the 2.0 series. For older changes see the
[1.x series changelog](https://github.com/nikic/PHP-Parser/blob/1.x/CHANGELOG.md) and the
[0.9 series changelog](https://github.com/nikic/PHP-Parser/blob/0.9/CHANGELOG.md).**