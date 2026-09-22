<template>
  <section style="display: inline-grid;">
    <VCard class="border-card info"  :style="'width:'+ props.height +'px;height:'+props.height +'px'">
      <canvas :id="props.elemenID" :height="props.height" :width="props.width"></canvas>
    </VCard>
    <VButton type="button" rounded outlined color="danger" raised icon="feather:trash" class="mt-2"
      @click="clearCanvas(props.elemenID)"> Clear
    </VButton>
  </section>
</template>
<script  lang="ts">
import sleep from '/@src/utils/sleep'
import $ from "jquery";

export default {
  props: {
    elemenID: String,
    height: String,
    width: String
  },
  setup(props) {

    const getPosition = (mouseEvent: any, sigCanvas: any) => {
      let rect = sigCanvas.getBoundingClientRect();
      return {
        X: mouseEvent.clientX - rect.left,
        Y: mouseEvent.clientY - rect.top
      };
    }
    const sign = () => {

      let sigCanvas: any = document.getElementById(props.elemenID);
      // sigCanvas.height = 500
      // sigCanvas.width = 500
      let context = sigCanvas.getContext("2d");
      context.strokeStyle = "blue";
      context.lineJoin = "round";
      context.lineWidth = 2;
      let is_touch_device = 'ontouchstart' in document.documentElement;

      if (is_touch_device) {

        let drawer: any = {
          isDrawing: false,
          touchstart: function (coors: any) {
            context.beginPath();
            context.moveTo(coors.x, coors.y);
            this.isDrawing = true;
          },
          touchmove: function (coors: any) {
            if (this.isDrawing) {
              context.lineTo(coors.x, coors.y);
              context.stroke();
            }
          },
          touchend: function (coors: any) {
            if (this.isDrawing) {
              this.touchmove(coors);
              this.isDrawing = false;
            }
          }
        };


        function draw(event: any) {
          let coors = {
            x: event.targetTouches[0].pageX,
            y: event.targetTouches[0].pageY
          };

          let obj = sigCanvas;

          if (obj.offsetParent) {

            do {
              coors.x -= obj.offsetLeft;
              coors.y -= obj.offsetTop;
            }

            while ((obj = obj.offsetParent) != null);
          }


          drawer[event.type](coors);
        }


        sigCanvas.addEventListener('touchstart', draw, false);
        sigCanvas.addEventListener('touchmove', draw, false);
        sigCanvas.addEventListener('touchend', draw, false);


        sigCanvas.addEventListener('touchmove', function (event: any) {
          event.preventDefault();

        }, false);
      } else {

        $("#" + props.elemenID).mousedown(function (mouseEvent: any) {

          let position = getPosition(mouseEvent, sigCanvas);
          context.moveTo(position.X, position.Y);
          context.beginPath();
          $(this).mousemove(function (mouseEvent: any) {
            drawLine(mouseEvent, sigCanvas, context);
          }).mouseup(function (mouseEvent: any) {
            finishDrawing(mouseEvent, sigCanvas, context);
          }).mouseout(function (mouseEvent: any) {
            finishDrawing(mouseEvent, sigCanvas, context);
          });
        });

      }
    }
    const drawLine = (mouseEvent: any, sigCanvas: any, context: any) => {

      let position = getPosition(mouseEvent, sigCanvas);

      context.lineTo(position.X, position.Y);
      context.stroke();
    }
    const finishDrawing = (mouseEvent: any, sigCanvas: any, context: any) => {
      drawLine(mouseEvent, sigCanvas, context);

      context.closePath();
      $(sigCanvas).unbind("mousemove")
        .unbind("mouseup")
        .unbind("mouseout");
    }
    const clearCanvas = (canvas: any) => {

      var sigCanvas: any = document.getElementById(canvas);
      var context = sigCanvas.getContext("2d");
      context.clearRect(0, 0, sigCanvas.width, sigCanvas.height);

    }
    const fetchSignature = async () => {
      await sleep(1000)
      sign()
    }
    fetchSignature()

    return {
      fetchSignature,
      clearCanvas,
      finishDrawing,
      drawLine,
      sign,
      getPosition,
      props
    };
  }
}
</script>
