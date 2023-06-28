import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    #sendBtn;

    connect() {
        this.#sendBtn = this.element.querySelector('#sendBtn');

        document.addEventListener('dropzone:change', this.#onChange.bind(this));
        document.addEventListener('dropzone:clear', this.#onClear.bind(this));
    }

    disconnect() {
        document.removeEventListener('dropzone:change', this.#onChange.bind(this));
        document.removeEventListener('dropzone:clear', this.#onClear.bind(this));
    }

    #onChange() {
        this.#sendBtn.removeAttribute('disabled');
        this.#removeFormErrors();
    }

    #onClear() {
        this.#sendBtn.setAttribute('disabled', 'disabled');
    }

    #removeFormErrors() {
        this.element.querySelectorAll('.invalid-feedback').forEach((element) => element.remove());
    }
}
