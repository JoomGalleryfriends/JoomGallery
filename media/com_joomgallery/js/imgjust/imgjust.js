class ImgJust {
	idealHeight = 150
	rowGap = 0
	columnGap = 0
	paddingLeft = 0
	paddingRight = 0
	paddingTop = 0
	paddingBottom = 0
	reload() {
		const container = this.container;
		const computedStyle = getComputedStyle(container);
		const imgs = this.imgs;
		const containerWidth = container.getBoundingClientRect().width
				- this.paddingLeft - this.paddingRight;
		if (containerWidth <= 0)
			throw "ImgJust Error: No room for images. Client width is too small."
		const idealImgWidths = [];

		// Compute ideal 
		for (var i = 0; i < imgs.length; i++)
			idealImgWidths.push(imgs[i].naturalWidth * this.idealHeight / imgs[i].naturalHeight);

		const rowBadness = [];
		const rowStart = [];

		// Complete first row
		var badness = new Map();
		var start = new Map();
		var totalWidth = -this.columnGap;
		for (var end = 0; end < imgs.length; end++) {
			totalWidth += idealImgWidths[end] + this.columnGap;
			const dif = totalWidth - containerWidth;
			badness.set(end, dif * dif);
			start.set(end, 0);
		}
		rowBadness.push(badness);
		rowStart.push(start);

		// All other rows
		for (var row = 1; row < imgs.length; row++) {
			badness = new Map();
			start = new Map();

			// for each possible terminating image
			for (var end = row; end < imgs.length; end++) {

				// compute best starting image
				totalWidth = idealImgWidths[end];
				const dif = totalWidth - containerWidth;
				var minBad = dif * dif + rowBadness[row - 1].get(end - 1);
				var bestBeg = end;
				for (var beg = end - 1; beg >= row; beg--) {
					totalWidth += idealImgWidths[beg] + this.columnGap;
					const dif = totalWidth - containerWidth;
					const bad = dif * dif + rowBadness[row - 1].get(beg - 1);
					if (bad < minBad) {
						minBad = bad;
						bestBeg = beg;
					}
				}
				badness.set(end, minBad);
				start.set(end, bestBeg);
			}
			rowBadness.push(badness);
			rowStart.push(start);
		}

		// Find best terminating row
		var row = 0;
		var bestLastRow = row;
		var minBad = rowBadness[row].get(imgs.length - 1);
		for (row++; row < rowBadness.length; row++) {
			const bad = rowBadness[row].get(imgs.length - 1);
			if (bad < minBad) {
				minBad = bad;
				bestLastRow = row;
			}
		}

		// Assign start and stop index to each row
		var rowRanges = [];
		var prevRowStart = imgs.length;
		for (var row = bestLastRow; row >= 0; row--) {
			const end = prevRowStart - 1;
			const start = rowStart[row].get(end);
			rowRanges.unshift({start: start, end: end});
			prevRowStart = start;
		}

		// Format images
		for (var range of rowRanges) {
			var totalImgWidth = 0;
			for (var i = range.start; i <= range.end; i++) {
				totalImgWidth += idealImgWidths[i];
				imgs[i].style.marginBottom = this.rowGap + "px";
			}
			const containerWidthSpace = containerWidth - (range.end - range.start) * this.columnGap;
			const newHeight = Math.round(this.idealHeight * containerWidthSpace / totalImgWidth);
			var newRowWidth = 0;
			for (var i = range.start; i < range.end; i++) {
				const newWidth = Math.round(idealImgWidths[i] * containerWidthSpace / totalImgWidth);
				newRowWidth += newWidth + this.columnGap;
				imgs[i].style.width = newWidth + "px";
				imgs[i].style.height = newHeight + "px";
				imgs[i].style.marginRight = this.columnGap + "px";
			}
			imgs[range.end].style.width = containerWidth - newRowWidth + "px";
			imgs[range.end].style.height = newHeight + "px";
			imgs[range.end].style.marginRight = "0";
		}
		const lastRange = rowRanges[rowRanges.length - 1];
		for (var i = lastRange.start; i <= lastRange.end; i++)
			imgs[i].style.marginBottom = "0";

		// All done. Now make visible.
		for (const img of imgs)
			img.style.display = "block";
	}

	addImages(imgs) {
		for (const img of imgs) {
			img.style.display = "none";
			this.imgs.push(img);
		}
	}

	constructor(container, imgs = [], options = {}) {
		for (var [key, val] of Object.entries(options))
			this[key] = val;
		if (!container)
			throw "ImgJust Error: missing first argument in constructor: container"
		if (!imgs)
			throw "ImgJust Error: missing second argument in constructor: imgs"
		this.container = container;
		this.imgs = [];

		container.style.display = "flex";
		container.style.flexFlow = "row wrap";
		container.style.paddingTop = this.paddingTop + "px";
		container.style.paddingBottom = this.paddingBottom + "px";
		container.style.paddingLeft = this.paddingLeft + "px";
		container.style.paddingRight = this.paddingRight + "px";

		this.addImages(imgs);
		this.reload()

		addEventListener("resize", _ => this.reload());
	}
}