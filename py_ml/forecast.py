import mysql.connector
import pandas as pd
from datetime import datetime, timedelta
from sklearn.linear_model import LinearRegression

def run_ml_forecast():
    db = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="capstone_1"
    )
    cursor = db.cursor()

    query = """
        SELECT DATE_FORMAT(date, '%Y-%m') as m, SUM(revenue) as total_rev
        FROM (
            SELECT date_today as date, total_collected as revenue FROM dispatch_sessions WHERE status = 'Completed'
            UNION ALL
            SELECT order_date as date, subtotal as revenue FROM retail_orders
        ) as combined_sales
        GROUP BY m ORDER BY m ASC;
    """
    
    df = pd.read_sql(query, db)
    
    if len(df) < 1:
        print("Not enough historical data to train the machine learning model.")
        return
    df['time_index'] = range(1, len(df) + 1)
    
    X = df[['time_index']]
    y = df['total_rev']

    model = LinearRegression()
    model.fit(X, y)

    next_time_index = len(df) + 1
    next_month_df = pd.DataFrame([[next_time_index]], columns=['time_index'])
    predicted_val = model.predict(next_month_df)[0]

    predicted_val = max(0.0, round(float(predicted_val), 2))

    last_month_str = df['m'].iloc[-1]
    last_month_dt = datetime.strptime(last_month_str, '%Y-%m')
    next_month_dt = (last_month_dt.replace(day=28) + timedelta(days=4))
    next_month_str = next_month_dt.strftime('%Y-%m')

    save_query = """
        INSERT INTO ml_predictions (target_period, predicted_revenue) 
        VALUES (%s, %s)
        ON DUPLICATE KEY UPDATE predicted_revenue = VALUES(predicted_revenue);
    """
    cursor.execute(save_query, (next_month_str, predicted_val))
    db.commit()
    
    cursor.close()
    db.close()
    print(f"Success!  ML prediction for {next_month_str}: ₱{predicted_val}")

if __name__ == "__main__":
    run_ml_forecast()