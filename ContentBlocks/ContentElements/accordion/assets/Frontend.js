document.addEventListener("DOMContentLoaded", function () {
  let acc = document.getElementsByClassName("jv-accordion-item");
  console.log("acc script is active and found " + acc.length + " elements");
  for (jv_i = 0; jv_i < acc.length; jv_i++) {
    console.log(acc[jv_i]);
    acc[jv_i].addEventListener("click", function () {
      this.classList.toggle("active");
      console.log("clicked on " + acc[jv_i]);
      let jv_panel = this.nextElementSibling;
      jv_panel.classList.toggle("opened");
      if (jv_panel.style.maxHeight) {
        jv_panel.style.maxHeight = null;
      } else {
        jv_panel.style.maxHeight = jv_panel.scrollHeight + "px";
      }
    });
  }
});

