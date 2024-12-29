# dci-php

A PHP implementation of the DCI (Data, Context, and Interaction) programming paradigm.

The best place to start is probably the examples folder. Additional documentation for this library is not yet available
(other than comments in the code), but in the meantime, for an introduction to DCI,
see the article:

"Working with objects - in computer and mind"
at http://fulloo.info/Documents/.

Another very good (albeit somewhat older) introductory article is:
http://www.artima.com/articles/dci_vision.html

For more articles and information about DCI, see:
http://fulloo.info/

And this book by DCI's co-creator, which has a chapter on DCI:
Lean Architecture: for Agile Software Development
James O. Coplien and Gertrud Bjørnvig

You can also contact Matt Browne, the author of this library, at mbrowne83 [at] gmail [dot] com.

## Note on object identity

This library preserves object identity when adding roles to objects, as proven by the successful implementation of the DCI [Dijkstra example](examples/Dikjstra/), which was designed to showcase and test how object identity needs to work to implement DCI (see https://fulloo.info/ for the Dijkstra example in other languages).

But there is one "gotcha" to be aware of: within role methods, `$this` does not have the same identity as the role-playing object, and is technically a wrapper object. The wrapper is needed in PHP because otherwise, it would not be possible to refer back to the context object, which the wrapper makes available as `$this->context`. (`$this->context` is necessary in order for roles to be able to reference and use the other roles in the context.) There are two ways around this object identity problem:

1. Completely avoid using `$this` when calling other methods of the current role, and always refer to the role by its name, e.g. for a role `MyRole`, instead of calling `$this->someRoleMethod()`, we could call `$this->context->MyRole->someRoleMethod()`.

2. (The more concise option) Use `$this->self` if you need a reference to the role-playing object itself. See the `CurrentNode` and `NeighborNode` roles in the [Dijkstra context](examples/Dikjstra/CalculateShortestPath.php) for an example of this.
