<?hh // strict
namespace Px;

type NextSignal = mixed; // void | true
type Consumer<-T> = (function(T): Awaitable<NextSignal>); // async might be work, mixed might be cancellation
type Supplier<+T> = (function(Consumer<T>): Awaitable<mixed>); // async is just the lifetime of the chain up to this point
type Operator<-Tu, +Tv> = (function(Supplier<Tu>): Supplier<Tv>);
type Hole<-T> = (function(Supplier<T>): Awaitable<mixed>);

const bool CANCEL = true; // return this (really anything non-null) from any consumer to stop the flow