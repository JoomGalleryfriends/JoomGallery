# ImgJust
## Credits
Thanks to the original repository of this library:
https://github.com/hikir1/ImgJust

## Justified Image Wall Generator
Use ImgJust to create a wall of images on your website.
It's based on the LaTex paragraph justifying algorithm.
Images are scaled while maintaing their aspect ratios.
The source code is simple: it fits on 2 pages and uses
VanillaJS, so you don't have to worry about dependencies,
efficiency, or page load times.

## Algorithm

Given an ideal height, images are carefully put in rows
so that the width of each row is as close to the width of their
container as possible. Then the rows are scaled to fit their
container perfectly.

## Usage

Include the source code in your web page:
```html
<script src="path/to/imgjust.js"></script>
```

Once the page has loaded, use the ImgJust constructor,
passing in the container for the wall, an array
of HTMLImageElement objects, and an optional options object:
```js
addEventListener("load", _ => {
  const container = document.querySelector(".imgjust");
  const imgs = document.querySelectorAll(".imgjust img");
  const options = {
    idealHeight: 150,
  };
  const imgjust = new ImgJust(container, imgs, options);
});
```

## Options & Defaults

- idealHeight: 150
- rowGap = 0
- columnGap = 0
- paddingLeft = 0
- paddingRight = 0
- paddingTop = 0
- paddingBottom = 0

## ImgJust Public Methods

- `constructor(container, imgs=[], options={})`
- `reload()`
- `addImages(imgs)`

## Styling

Style the components normally using CSS.

*Note: if adding space, make sure to account for
it by changing the above mentioned padding and gap options.*

*Another Note: You may want to set the root `overflow-y`
property to `scroll` so that the width of the ImgJust
container doesn't change after being rendered.*

```css
:root {
  overflow-y: scroll;
}
.imgjust {
  background: red;
}
.imgjust img {
  border: 1px solid black;
}
```

## Nested Elements

The DOM is not altered in any way,
so images can safely be placed in other
elements.

```html
<div class="imgjust">
  <a href="larger-img-1.jpg">
    <img src="icon-1.jpg">
  </a>
  <a href="larger-img-2.jpg">
    <img src="icon-2.jpg">
  </a>
  ...
</div>
```
