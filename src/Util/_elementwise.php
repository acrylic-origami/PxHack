<?hh // strict
namespace HHx\Util;
use HHx\{Consumer, Operator};
function _elementwise<Tu, Tv>((function(Tu, Consumer<Tv>): Awaitable<mixed>) $f): Operator<Tu, Tv> {
	return $up ==> $down ==> $up($v ==> $f($v, $down));
}