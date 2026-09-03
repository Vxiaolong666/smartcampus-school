function Clock() {
  var date = new Date();
  this.year = date.getFullYear();
  this.month = date.getMonth() + 1;
  this.date = date.getDate();
  this.hour = date.getHours() < 10 ? "0" + date.getHours() : date.getHours();
  this.minute = date.getMinutes() < 10 ? "0" + date.getMinutes() : date.getMinutes();
  this.second = date.getSeconds() < 10 ? "0" + date.getSeconds() : date.getSeconds();
  this.toDetailDate = function() {
    return this.hour + ":" + this.minute + ":" + this.second;
  };
  this.display = function(ele) {
    var clock = new Clock();
    if (ele) {
        ele.innerHTML = clock.toDetailDate();
        window.setTimeout(function() {clock.display(ele);}, 1000);
    }
  };
}

document.addEventListener('DOMContentLoaded', function() {
    var clockElement = document.getElementById("clock");
    if (clockElement) {
        var clock = new Clock();
        clock.display(clockElement);
    }
});