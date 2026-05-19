<?php
header('Content-Type: application/json');


$apiKey = getenv('GEMINI_API_KEY'); 


$user_message = $_POST['message'] ?? '';

if (empty(trim($user_message))) {
    http_response_code(400); // Bad Request
    echo json_encode(['reply' => 'Please type a message.']);
    exit();
}


$bot_response = generate_chatbot_response($user_message, $apiKey);


echo json_encode(['reply' => $bot_response]);



function generate_chatbot_response($message, $apiKey = "") {
    
    if (!empty($apiKey)) {
        $api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;
        
        $systemInstruction = "You have two primary roles.

        **Role 1: Kalinga Clinic Portal Guide.** You are an AI assistant for the Kalinga Medical Clinic PAGASA patient portal. Your goal is to guide users to the correct pages for appointments, medical history, billing, etc. You are aware of the clinic's specific details.
        - **Name:** Kalinga Medical Clinic PAGASA
        - **Location:** Bahayang Pag Asa Subd, 79 Jacinto St, Imus, 4103 Cavite
        - **Hours:** We are open Monday to Sunday, from 9:00 AM to 5:00 PM.
        
        **Role 2: General Health Information Source.** You can answer general knowledge and health-related questions.

        **CRITICAL SAFETY PROTOCOL: THE MEDICAL DISCLAIMER**
        When a user asks a general health question, you MUST ALWAYS end your response with the following disclaimer, separated by a line:
        ---
        *This information is for educational purposes only and is not a substitute for professional medical advice, diagnosis, or treatment. Always seek the advice of your physician or another qualified health provider with any questions you may have regarding a medical condition.*

        **ABSOLUTE RULE:** NEVER diagnose a user or give personalized medical advice. If a user asks for a diagnosis, you must refuse and guide them to consult a doctor.";

        $data = [
            'contents' => [['parts' => [['text' => $message]]]],
            'systemInstruction' => ['parts' => [['text' => $systemInstruction]]]
        ];
        
        $ch = curl_init($api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_TIMEOUT => 30
        ]);

        $api_result = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if (!$curl_error) {
            $response_data = json_decode($api_result, true);
            if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
                
                return $response_data['candidates'][0]['content']['parts'][0]['text'];
            }
        } else {
            
            error_log("Gemini API cURL Error: " . $curl_error);
        }
    }

    
    $lower_message = strtolower($message);

    if (strpos($lower_message, 'location') !== false || strpos($lower_message, 'address') !== false) {
        return "We are located at **Bahayang Pag Asa Subd, 79 Jacinto St, Imus, 4103 Cavite**. Let me know if you need directions!";
    }
    if (strpos($lower_message, 'hours') !== false || strpos($lower_message, 'open') !== false) {
        return "Our clinic hours are from **9:00 AM to 5:00 PM every day**.";
    }
    if (strpos($lower_message, 'appointment') !== false || strpos($lower_message, 'schedule') !== false) {
        return "You can schedule an appointment by visiting the **'Appointment'** page on our main website or by calling us directly.";
    }
    if (strpos($lower_message, 'hello') !== false || strpos($lower_message, 'hi') !== false) {
        return "Hello! I'm here to help. What can I do for you today?";
    }
    if (strpos($lower_message, 'thank') !== false) {
        return "You're welcome! Is there anything else I can assist you with?";
    }

    
    return "I'm sorry, I'm having trouble connecting to my advanced knowledge base right now. Please try rephrasing, or ask me about our clinic's location and hours.";
}
?>