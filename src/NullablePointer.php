<?hh // strict
namespace HHx;
class NullablePointer<T> extends Pointer<?T> {
	<<__Override>>
	public function __construct(protected ?T $v = null) { parent::__construct($v); }
}