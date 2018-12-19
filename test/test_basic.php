<?hh // strict
require_once(__DIR__ . '/../vendor/hh_autoload.php');
use namespace HH\Asio;
use HH\Asio\Scheduler as S;

use function Px\{publish};
use function Px\Util\{share, P2S};

<<__Entrypoint>>
function test_basic(): void {
	$S = publish(P2S((async {
		for($i = 0; ; $i++) { await Asio\usleep(intval(100E3)); yield $i; }
	})));
	for($i = 0; $i < 3; $i++)
		S::launch($S((async $v ==> var_dump($v))));
	
	Asio\join(S::run());
}
