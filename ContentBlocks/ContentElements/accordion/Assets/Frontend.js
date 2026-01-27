let acc = document.getElementsByClassName("accordion-item");

for (jv_i = 0; jv_i < acc.length; jv_i++) {
  acc[jv_i].addEventListener("click", function () {
    this.classList.toggle("active");

    let jv_panel = this.nextElementSibling;
    if (jv_panel.style.maxHeight) {
      jv_panel.style.maxHeight = null;
    } else {
      jv_panel.style.maxHeight = jv_panel.scrollHeight + "px";
    }
  });
}
