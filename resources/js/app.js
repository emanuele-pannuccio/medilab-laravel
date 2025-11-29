import './bootstrap';


const userIdMeta = document.querySelector('meta[name="user-id"]');

if (userIdMeta) {

    const userId = userIdMeta.content;

    window.Echo.private('doctors.' + userId)
        .listen('.message.sent', (e) => {
            console.log(e);
        });
}