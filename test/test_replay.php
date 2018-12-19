<?hh // strict
require_once(__DIR__ . '/../vendor/hh_autoload.php');
use namespace HH\Asio;
use HH\Asio\Scheduler as S;
use function HHx\{replay, publish};
use function HHx\Source\{interval};
use function HHx\Util\{P2S, share};
<<__Entrypoint>>
function test_replay(): void {
	$source = replay(P2S(share(interval(intval(100E3)))));
	S::launch($source(async $v ==> { echo "1: $v\n"; }));
	Asio\join(Asio\v(vec[
		S::run(),
		async {
			await HH\Asio\usleep(intval(1E6));
			S::launch($source(async $v ==> { echo "2: $v\n"; }));
		}
	]));
}