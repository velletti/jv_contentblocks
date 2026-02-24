let accgroup = document.getElementsByClassName("jv-accordion-group");


for (jv_i = 0; jv_i < accgroup.length; jv_i++) {
  accgroup[jv_i].addEventListener("click", function () {
    this.classList.toggle("active");

    let jv_panel = this.nextElementSibling;
    if (jv_panel.style.maxHeight) {
      jv_panel.style.maxHeight = null;
      jv_panel.style.paddingTop = "0";
      jv_panel.style.paddingBottom = "0";
    } else {
      jv_panel.style.paddingTop = "1em";
      jv_panel.style.paddingBottom = "1em";
      jv_panel.style.maxHeight = "fit-content";
    }

    if (history.pushState) {
      const url = new URL(window.location);
      const params = url.searchParams;

      if (this.classList.contains("active")) {
        // Accordion is OPEN → set/update parameter
        params.set("jv-accordion-group", this.id);
      } else {
        // Accordion is CLOSED → remove parameter
        params.delete("jv-accordion-group");
      }

      url.search = params.toString();


      // Update browser history
      history.pushState({}, "", url.toString());
    }

  });
}

let acc = document.getElementsByClassName("jv-accordion-item");


for (jv_ii = 0; jv_ii < acc.length; jv_ii++) {
  acc[jv_ii].addEventListener("click", function () {
    this.classList.toggle("active");

    let jv_panelgroup = this.nextElementSibling;
    if (jv_panelgroup.style.maxHeight) {
      jv_panelgroup.style.maxHeight = null;
      jv_panelgroup.style.paddingTop = "0";
      jv_panelgroup.style.paddingBottom = "0";
    } else {
      jv_panelgroup.style.paddingTop = "1em";
      jv_panelgroup.style.paddingBottom = "1em";
      jv_panelgroup.style.maxHeight = "fit-content";

    }
    if (history.pushState) {
      const url = new URL(window.location);
      const params = url.searchParams;

      if (this.classList.contains("active")) {
        // Accordion is OPEN → set/update parameter
        params.set("jv-accordion", this.id);
      } else {
        // Accordion is CLOSED → remove parameter
        params.delete("jv-accordion");
      }

      url.search = params.toString();


      // Update browser history
      history.pushState({}, "", url.toString());
    }

  });
}



document.addEventListener("DOMContentLoaded", function () {

  const url = new URL(window.location);
  const openGroupId = url.searchParams.get("jv-accordion-group");
  if (!openGroupId) return; // nothing to do
  // Find the accordion button
  const btnGroup = document.getElementById(openGroupId);

  if (!btnGroup) return; // ID not found on the page

  // If it is already open, do nothing
  if (!btnGroup.classList.contains("active")) {
    btnGroup.click(); // simulate user click to open
  }
  const openId = url.searchParams.get("jv-accordion");

  if (!openId) return; // nothing to do

  const btn = document.getElementById(openId);

  if (!btn) return; // ID not found on the page

  // If it is already open, do nothing
  if (!btn.classList.contains("active")) {
    btn.click(); // simulate user click to open
  }


  // OPTIONAL: scroll to accordion
  btn.scrollIntoView({ behavior: "smooth", block: "center" });
});
