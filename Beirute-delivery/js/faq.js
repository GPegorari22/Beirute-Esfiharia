const faqItems = document.querySelectorAll(".faq-item");

faqItems.forEach(item => {
    const question = item.querySelector(".faq-question");

    question.addEventListener("click", () => {
        const openItem = document.querySelector(".faq-item.active");
        
        if (openItem && openItem !== item) {
            openItem.classList.remove("active");
            openItem.querySelector(".faq-answer").style.maxHeight = 0;
        }

        item.classList.toggle("active");

        const answer = item.querySelector(".faq-answer");

        if (item.classList.contains("active")) {
            answer.style.maxHeight = answer.scrollHeight + "px";
        } else {
            answer.style.maxHeight = 0;
        }
    });
});