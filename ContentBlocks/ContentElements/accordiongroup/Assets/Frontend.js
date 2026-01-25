let accgroup = document.getElementsByClassName("accordion-group");
let i;

for (i = 0; i < accgroup.length; i++) {
  accgroup[i].addEventListener("click", function () {
    this.classList.toggle("active");

    let panel = this.nextElementSibling;
    if (panel.style.maxHeight) {
      panel.style.maxHeight = null;
    } else {
      panel.style.maxHeight = "fit-content";
    }
  });
}

let acc = document.getElementsByClassName("accordion-item");
let ii;

for (ii = 0; ii < acc.length; ii++) {
  acc[ii].addEventListener("click", function () {
    this.classList.toggle("active");

    let panel = this.nextElementSibling;
    if (panel.style.maxHeight) {
      panel.style.maxHeight = null;
    } else {
      panel.style.maxHeight = "fit-content";
    }
  });
}