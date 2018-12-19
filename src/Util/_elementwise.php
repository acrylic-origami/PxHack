<?hh // strict
namespace Px\Util;
use Px\{Consumer, Operator};
function _elementwise<Tu, Tv>((function(Tu, Consumer<Tv>): Awaitable<mixed>) $f): Operator<Tu, Tv> {
	return $up ==> $down ==> $up($v ==> $f($v, $down));
}