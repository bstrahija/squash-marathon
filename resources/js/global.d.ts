declare module 'canvas-confetti' {
    const confetti: (...args: any[]) => any;
    export default confetti;
}

declare global {
    interface Window {
        launchMatchDoneConfetti?: () => void;
    }
}

export {};
