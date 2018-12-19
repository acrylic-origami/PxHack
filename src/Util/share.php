<?hh // strict
namespace Px\Util;
use Px\ShareIterator;
function share<T>(AsyncIterator<T> $P): ShareIterator<T> {
	return new ShareIterator($P);
}