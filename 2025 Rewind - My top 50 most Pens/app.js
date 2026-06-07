console.clear();
const PENS = [
  {
    url: "https://codepen.io/cbolson/pen/azoVvZm",
    img: "clock-wheel.webp",
    title: "100 Year Clock Wheel",
    likes: 665,
    views: 12511
  },
  {
    url: "https://codepen.io/cbolson/pen/vELrOPz",
    img: "curved-scrollbar.webp",
    title: "Custom curved corner scrollbars",
    likes: 641,
    views: 12013
  },
  {
    url: "https://codepen.io/cbolson/pen/EaKKJdq",
    img: "mask-gallery.webp",
    title: "Mask Gallery",
    likes: 13,
    views: 673
  },
  {
    url: "https://codepen.io/cbolson/pen/NPNjvOQ",
    img: "flip-text-lando.webp",
    title: "Flip Text, Lando Morris Style",
    likes: 99,
    views: 1873
  },
  {
    url: "https://codepen.io/cbolson/pen/OPyvEaE",
    img: "superheroes-carousel.webp",
    title: "Superheroes Carousel",
    likes: 101,
    views: 3431
  }, 
  {
    url: "https://codepen.io/cbolson/pen/NPqrxbb",
    img: "squircles-gallery.webp",
    title: "Squircles Gallery with view-transition",
    likes: 115,
    views: 5145
  },
  {
    url: "https://codepen.io/cbolson/pen/MYavyqQ",
    img: "corner-shape-generator.webp",
    title: "corner-shape superellipse() generator",
    likes: 231,
    views: 6786
  },
  {
    url: "https://codepen.io/cbolson/pen/ogxgRBJ",
    img: "custom-select-2.webp",
    title: "Custom Select List",
    likes: 20,
    views: 888
  },
  {
    url: "https://codepen.io/cbolson/pen/WbrWMbm",
    img: "corner-shape-gallery.webp",
    title: "Corner-Shape Gallery",
    likes: 79,
    views: 2323
  },
  {
    url: "https://codepen.io/cbolson/pen/VYawwgQ",
    img: "linked-lists.webp",
    title: "Linked Lists, multiple > single",
    likes: 8,
    views: 122
  },
  {
    url: "https://codepen.io/cbolson/pen/vEBWwxL",
    img: "wheel-timeline-2.webp",
    title: " Wheel Timeline",
    likes: 395,
    views: 10502
  },
  {
    url: "https://codepen.io/cbolson/pen/LEENGmB",
    img: "butterflies.webp",
    title: "Butterflies",
    likes: 90,
    views: 4025
  },
  {
    url: "https://codepen.io/cbolson/pen/raBJWOJ",
    img: "pokemon-gallery-2.webp",
    title: "Pokemon Slide Gallery",
    likes: 453,
    views: 8272
  },
  {
    url: "https://codepen.io/cbolson/pen/GgoMyVp",
    img: "bad-date-picker.webp",
    title: "Bad UX Date Picker",
    likes: 18,
    views: 1253
  },
  {
    url: "https://codepen.io/cbolson/pen/dPGWzQX",
    img: "dot-clock-2.webp",
    title: "Minimal Dot Clock",
    likes: 67,
    views: 905
  },
  {
    url: "https://codepen.io/cbolson/pen/dPGGRZd",
    img: "carousel-scroll.webp",
    title: "Carousel using ::scroll-* Demo 2",
    likes: 63,
    views: 1637
  },
  {
    url: "https://codepen.io/cbolson/pen/LEGVzpR",
    img: "carousel-scroll-1.webp",
    title: "Carousel using ::scroll-* Demo 1",
    likes: 29,
    views: 1163
  },
  {
    url: "https://codepen.io/cbolson/pen/jEbGZRB",
    img: "sphere-shadow.webp",
    title: "Sphere with shadow",
    likes: 17,
    views: 1147
  },
  {
    url: "https://codepen.io/cbolson/pen/NPGwpGY",
    img: "in-the-shade.webp",
    title: "In the Shade",
    likes: 29,
    views: 1018
   },
  {
    url: "https://codepen.io/cbolson/pen/XJmNPQw",
    img: "sliding-scroll-images.webp",
    title: "Sliding Images using animation-timeline",
    likes: 31,
    views: 1462
  },
  {
    url: "https://codepen.io/cbolson/pen/Byopzvy",
    img: "1000-followers-2.webp",
    title: "♥ 1,000 Followers on CodePen ♥",
    likes: 145,
    views: 2906
  },
  {
    url: "https://codepen.io/cbolson/pen/VYvYeag",
    img: "circular-nav.webp",
    title: "Circular Nav component",
    likes: 31,
    views: 962
  },
  {
    url: "https://codepen.io/cbolson/pen/EaVYoVd",
    img: "slide-out-nav.webp",
    title: "Slide-out nav with flip effect",
    likes: 79,
    views: 2517
  },
  {
    url: "https://codepen.io/cbolson/pen/raVXxmb",
    img: "grid-areas-attr.webp",
    title: "grid areas using attr()",
    likes: 100,
    views: 5009
  },
  {
    url: "https://codepen.io/cbolson/pen/VYYrpoV",
    img: "the-doctors.webp",
    title: "The Doctors",
    likes: 109,
    views: 3834
  },
  {
    url: "https://codepen.io/cbolson/pen/wBBaegL",
    img: "goldfish.webp",
    title: "Goldfish",
    likes: 30,
    views: 2351
  },
  {
    url: "https://codepen.io/cbolson/pen/raaBmwy",
    img: "moon-cards.webp",
    title: "The Moon - Glowing Cards",
    likes: 124,
    views: 6792
  },
  {
    url: "https://codepen.io/cbolson/pen/raaBgPz",
    img: "cut-out-curved-corners.webp",
    title: "Cutout Curved Corners using SVG",
    likes: 38,
    views: 870
  },
  {
    url: "https://codepen.io/cbolson/pen/ZYEdGQX",
    img: "easter-cards.webp",
    title: "Easter Card Carousel",
    likes: 149,
    views: 10712
  },
  {
    url: "https://codepen.io/cbolson/pen/mydYrKg",
    img: "poetic-cards.webp",
    title: "Stacked Poetic Cards",
    likes: 159,
    views: 4772
  },
  {
    url: "https://codepen.io/cbolson/pen/bNGjarJ",
    img: "flip-text.webp",
    title: "Flip Text",
    likes: 12,
    views: 255
  },
  {
    url: "https://codepen.io/cbolson/pen/vEYrqop",
    img: "cicada.webp",
    title: "Cicada Principal synthesizer",
    likes: 31,
    views: 1400
  },
  {
    url: "https://codepen.io/cbolson/pen/PwoKMXd",
    img: "404-lost-in-space.webp",
    title: "404 - Lost in space",
    likes: 41,
    views: 1158
  },
  {
    url: "https://codepen.io/cbolson/pen/qEBjpeo",
    img: "bugliest-bug.webp",
    title: "The Bugliest Bug - custom cursors",
    likes: 134,
    views: 3984
  },
  {
    url: "https://codepen.io/cbolson/pen/zxYdrYx",
    img: "nonstop.webp",
    title: "Nonstop",
    likes: 9,
    views: 214
  },
  {
    url: "https://codepen.io/cbolson/pen/WbNONXK",
    img: "the-fly.webp",
    title: "The Fly",
    likes: 30,
    views: 1138
  },
  {
    url: "https://codepen.io/cbolson/pen/VYwbypx",
    img: "jumpy-cards.webp",
    title: "Jumpy Intro Cards",
    likes: 10,
    views: 786
  },
  {
    url: "https://codepen.io/cbolson/pen/MYWarzy",
    img: "dynamic-patterns.webp",
    title: "Dynamic patterns with config panel",
    likes: 56,
    views: 1302
  },
  {
    url: "https://codepen.io/cbolson/pen/yyLYBBg",
    img: "sticky-rotating-cards.webp",
    title: "Sticky rotating cards with scroll-timeline animation",
    likes: 12,
    views: 294
  },
  {
    url: "https://codepen.io/cbolson/pen/WbNbwrO",
    img: "mini-gallery.webp",
    title: "Mini Gallery",
    likes: 37,
    views: 1079
  },
  {
    url: "https://codepen.io/cbolson/pen/QwWWrgK",
    img: "electric-switch.webp",
    title: "Electric Circuit Switch",
    likes: 58,
    views: 2132
  },
  {
    url: "https://codepen.io/cbolson/pen/yyBmLvb",
    img: "retro-switch.webp",
    title: "Retro toggle switch",
    likes: 10,
    views: 1394
  },
  {
    url: "https://codepen.io/cbolson/pen/EaYzgaq",
    img: "3-way-toggle.webp",
    title: "3-way toggle",
    likes: 81,
    views: 1713
  },
  {
    url: "https://codepen.io/cbolson/pen/ByBPjGr",
    img: "flip-gallery.webp",
    title: "Flip gallery",
    likes: 93,
    views: 2881
  },
  {
    url: "https://codepen.io/cbolson/pen/WbePgJN",
    img: "dance-to-express.webp",
    title: "Dance to express",
    likes: 14,
    views: 736
  },
  {
    url: "https://codepen.io/cbolson/pen/dPbKpyG",
    img: "lets-dance.webp",
    title: "Let's dance! Rotating disco ball on scroll",
    likes: 30,
    views: 1293
  },
  {
    url: "https://codepen.io/cbolson/pen/WbeJKad",
    img: "random-rotation.webp",
    title: "Random Rotation",
    likes: 16,
    views: 613
  },
  {
    url: "https://codepen.io/cbolson/pen/MYgrjrm",
    img: "shuffle.webp",
    title: "Shuffling Effect in Pure CSS",
    likes: 302,
    views: 7268
  },
  {
    url: "https://codepen.io/cbolson/pen/wBGeyzL",
    img: "math.webp",
    title: "Math using CSS only function() & if() functions",
    likes: 33,
    views: 1337
  },
  {
    url: "https://codepen.io/cbolson/pen/OJeaYgZ",
    img: "scroll-indicator.webp",
    title: "Scroll driven indicator using animation-timeline",
    likes: 16,
    views: 3976
  },
  {
    url: "https://codepen.io/cbolson/pen/LENGENp",
    img: "stepped-selector.webp",
    title: "Stepped Value Selector",
    likes: 33,
    views: 595
  },
  {
    url: "https://codepen.io/cbolson/pen/QwyZKBp",
    img: "animated-svg-path.webp",
    title: "Animated SVG path with stops",
    likes: 53,
    views: 550
  },
  {
    url: "https://codepen.io/cbolson/pen/pvgdJmx",
    img: "spooky-spin.webp",
    title: "Spooky Spin",
    likes: 11,
    views: 744
  },
  {
    url: "https://codepen.io/cbolson/pen/QwjRvrr",
    img: "gradient-border-overlay.webp",
    title: "Gradient overlay using border-image",
    likes: 14,
    views: 858
  },
  {
    url: "https://codepen.io/cbolson/pen/KwdZzOM",
    img: "pie-chart-attr.webp",
    title: "Pie Chart using attr()",
    likes: 19,
    views: 882
  },
  {
    url: "https://codepen.io/cbolson/pen/PwNQWNa",
    img: "sibling-index-animation.webp",
    title: "Animation using sibling-index() + corner-shape",
    likes: 37,
    views: 2535
  },
  {
    url: "https://codepen.io/cbolson/pen/WbwPQdV",
    img: "cross-stitch.webp",
    title: "Christmas Cross-Stitch Messages",
    likes: 28,
    views: 681
  },
  {
    url: "https://codepen.io/cbolson/pen/gbrBwQX",
    img: "image-bars.webp",
    title: "Full Height Image Bars",
    likes: 15,
    views: 560
  },
  {
    url: "https://codepen.io/cbolson/pen/gbrdLOd",
    img: "snow-landscape.webp",
    title: "I wonder if the snow loves the trees",
    likes: 25,
    views: 698
  },
  {
    url: "https://codepen.io/cbolson/pen/JoXQJNq",
    img: "2025-rewind.webp",
    title: "2025 Rewind",
    likes: 27,
    views: 521
  }

];
/*
,
  {
    url: "",
    img: "",
    title: "",
    likes: ,
    views: 
  }
*/
// CONFIG
const FLIP_SPEED = 700;
const PENS_TO_DISPLAY = 61; // adjust as needed

// ANIMATIONS
const flipTiming = { duration: FLIP_SPEED, iterations: 1 };

const flipAnimationTop = [
    { transform: "rotateX(0)" },
    { transform: "rotateX(-90deg)" },
    { transform: "rotateX(-90deg)" }
];

const flipAnimationBottom = [
    { transform: "rotateX(90deg)" },
    { transform: "rotateX(90deg)" },
    { transform: "rotateX(0)" }
];

const flipAnimationTopReverse = [
    { transform: "rotateX(-90deg)" },
    { transform: "rotateX(-90deg)" },
    { transform: "rotateX(0)" }
];

const flipAnimationBottomReverse = [
    { transform: "rotateX(0)" },
    { transform: "rotateX(90deg)" },
    { transform: "rotateX(90deg)" }
];

// SELECTORS
const flipGallery = document.getElementById("flip-gallery");
const flipUnite = flipGallery.querySelectorAll(".unite");
const thumbsContainer = document.getElementById("gallery");

// STATE
let currentIndex = 0;

// SORT AND LIMIT PENS
function sortPens(pens, property) {
    return [...pens].sort((a, b) => (b[property] || 0) - (a[property] || 0));
}
const sortedPens = sortPens(PENS, "likes").slice(0, PENS_TO_DISPLAY);

// PRELOAD IMAGES
function preloadImages() {
    sortedPens.forEach(item => {
        const img = new Image();
        img.src = `https://raw.githubusercontent.com/cbolson/assets/refs/heads/main/codepen/2025/${item.img}`;
    });
}
preloadImages();

// CURRENT IMAGE
function setActiveImage(el, index) {
    const img = `https://raw.githubusercontent.com/cbolson/assets/refs/heads/main/codepen/2025/${sortedPens[index].img}`;
    el.style.backgroundImage = `url("${img}")`;
}

const DURATION_INFO = 300;

function setImageTitle(index) {
    const itemInfo = flipGallery.querySelector("#info");
    itemInfo.classList.add("hidden");

    setTimeout(() => {
        itemInfo.querySelector(".item-link").href = sortedPens[index].url;
			itemInfo.querySelector(".item-link").title = `See pen:  ${sortedPens[index].title}`;
			
        itemInfo.querySelector(".item-link").textContent = sortedPens[index].title;
        itemInfo.querySelector(".item-likes").textContent = new Intl.NumberFormat().format(sortedPens[index].likes);
        itemInfo.querySelector(".item-views").textContent = new Intl.NumberFormat().format(sortedPens[index].views);

        setTimeout(() => itemInfo.classList.remove("hidden"), 20);
    }, DURATION_INFO);
}

function updateActiveThumb(index) {
    document.querySelectorAll(".thumb.active").forEach(t => t.classList.remove("active"));

    const thumbs = document.querySelectorAll(".thumb");
    if (thumbs[index]) {
        thumbs[index].classList.add("active");
        thumbs[index].classList.add("visited");
    }
}

function updateGallery(index, isReverse = false) {
    const topAnimation = isReverse ? flipAnimationTopReverse : flipAnimationTop;
    const bottomAnimation = isReverse ? flipAnimationBottomReverse : flipAnimationBottom;

    flipGallery.querySelector(".overlay-top").animate(topAnimation, flipTiming);
    flipGallery.querySelector(".overlay-bottom").animate(bottomAnimation, flipTiming);

    flipUnite.forEach((el, idx) => {
        const delay = isReverse
            ? (idx === 1 || idx === 2 ? 0 : FLIP_SPEED - 200)
            : (idx === 1 || idx === 2 ? FLIP_SPEED - 200 : 0);

        setTimeout(() => setActiveImage(el, index), delay);
    });

    setTimeout(() => setImageTitle(index), FLIP_SPEED * 0.5);

    // update active thumbnail
    updateActiveThumb(index);
}

// NAV
function updateIndex(increment) {
    const newIndex = Number(increment);
    const isReverse = newIndex < 0;
    currentIndex = (currentIndex + newIndex + sortedPens.length) % sortedPens.length;
    updateGallery(currentIndex, isReverse);
}

document.querySelectorAll("[data-gallery-nav]").forEach(btn => {
    btn.addEventListener("click", () => updateIndex(parseInt(btn.dataset.galleryNav, 10)));
});

// THUMBS
function createThumbnails() {
    sortedPens.forEach((item, idx) => {
        const btn = document.createElement("button");
        btn.className = "thumb";
			btn.title = item.title;
        btn.style.backgroundImage = `url("https://raw.githubusercontent.com/cbolson/assets/refs/heads/main/codepen/2025/${item.img}")`;

        btn.addEventListener("click", () => {
            currentIndex = idx;
            updateGallery(currentIndex, false);
        });

        thumbsContainer.appendChild(btn);
    });
}
createThumbnails();

// INIT (random item)
currentIndex = Math.floor(Math.random() * sortedPens.length);

function defineFirstImg() {
    flipUnite.forEach(el => setActiveImage(el, currentIndex));
    setImageTitle(currentIndex);
    updateActiveThumb(currentIndex); // mark first thumb active
}
defineFirstImg();
