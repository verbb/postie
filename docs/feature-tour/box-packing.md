# Box Packing
Postie features several different mechanisms to pack boxes for accurate shipping quotes.

## Pack items individually
When chosen, each item in the cart will be packed in a box fitted to the item. When purchasing multiple quantities of the same item, each item will still be in an individual box.

## Pack items into boxes
This will fit all items in one or many boxes, as defined by you, or pre-set boxes defined by the provider. Any items that do not fit these box constraints are fitted to an individual box.

## Pack items into a single box
Given a collection of products, Postie will try to fit all items into a single box. Care should be taken with this approach, if an order contains large or bulk items, they might exceed provider allowances.

## 4D Box Packing
Our box packing algorithm is volume-based, and whilst it provides good results in most cases, it will never be as accurate as a real person packing a box (see [BIN Packing Problem](http://en.wikipedia.org/wiki/Bin_packing_problem)). 

At a high level, the algorithm works like this:

- Pack the largest (by volume) items first
- Pack vertically up the side of the box
- Pack side-by-side where the item under consideration fits alongside the previous item
- If more than 1 box is needed to accommodate all the items, then aim for boxes of roughly equal weight (e.g. 3 medium-sized size/weight boxes are better than 1 small light box and 2 that are large and heavy)
- Unpackable items are packed separately, using the item dimensions.

Refer to the [docs](https://www.boxpacker.io/en/stable/principles.html) for further reading.
