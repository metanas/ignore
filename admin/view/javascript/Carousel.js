class Carousel{
    constructor(element, option={}){
        this.element = element;
        this.options = object.assign({},{
            slideToScroll:1,
            slideVisible:1
        },option)
    }
}

document.addEventListener('DOMCotentLoaded',function () {
    new Carousel(document.querySelector(),{
        slideToScroll: 3,
        slideVisible: 5
    });

})
